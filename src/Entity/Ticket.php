<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use App\Entity\Dto\DesistementInput;
use App\Entity\Interface\EntrepriseOwnedInterface;
use App\Entity\Interface\LigneGareScopedInterface;
use App\Repository\TicketRepository;
use App\State\DesistementProcessor;
use App\State\SoftDeleteProcessor;
use App\State\TicketProcessor;
use App\State\UpdatedbyProcessor;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: TicketRepository::class)]
/*
    - Plus d'unicité (voyage, siege) : un siège peut porter plusieurs tickets sur le même voyage
      tant que leurs tronçons [montée, descente) ne se chevauchent pas (cf. TicketProcessor).
*/
#[ApiResource(
    security: "is_granted('IS_AUTHENTICATED_FULLY')",
    normalizationContext: ['groups' => ['read:Ticket', 'read:Base'], 'skip_null_values' => false],
    denormalizationContext: ['groups' => ['write:Ticket']],
    paginationItemsPerPage: 25,
    paginationClientItemsPerPage: true,
    order: ['createdAt' => 'DESC'],
    operations: [
        new GetCollection(
            security: "is_granted('VOIR', 'Ticket') or is_granted('ROLE_USER')",
            openapi: new Operation(
                summary: 'Liste des tickets',
                description: 'Permet de voir la liste des tickets',
                security: [['bearerAuth' => []]]
            )
        ),
        new Get(
            security: "is_granted('VOIR', object) or is_granted('ROLE_USER')",
            requirements: ['id' => '\d+'],
            openapi: new Operation(
                summary: 'Le ticket',
                description: 'Permet de voir un ticket',
                security: [['bearerAuth' => []]]
            )
        ),
        new Post(
            security: "is_granted('CREER', 'Ticket')",
            processor: TicketProcessor::class,
            openapi: new Operation(
                summary: 'Création du ticket',
                description: 'Permet de créer un ticket',
                security: [['bearerAuth' => []]]
            )
        ),
        new Patch(
            security: "is_granted('MODIFIER', object)",
            requirements: ['id' => '\d+'],
            processor: UpdatedbyProcessor::class,
            denormalizationContext: ['groups' => ['write:Ticket:update']],
            openapi: new Operation(
                summary: 'Modification du ticket',
                description: 'Permet de modifier un ticket',
                security: [['bearerAuth' => []]]
            )
        ),
        new Patch(
            security: "is_granted('SUPPRIMER', object)",
            uriTemplate: '/tickets/{id}/remove',
            requirements: ['id' => '\d+'],
            input: false,
            processor: SoftDeleteProcessor::class,
            openapi: new Operation(
                summary: 'Mise en corbeille du ticket',
                description: 'Permet de mettre un ticket en corbeille',
                security: [['bearerAuth' => []]]
            )
        ),
        new Patch(
            security: "is_granted('MODIFIER', object)",
            uriTemplate: '/tickets/{id}/desister',
            requirements: ['id' => '\d+'],
            input: DesistementInput::class,
            processor: DesistementProcessor::class,
            denormalizationContext: ['groups' => ['write:DesistementInput']],
            openapi: new Operation(
                summary: 'Désistement d\'un billet (report ou annulation)',
                description: 'Libère le siège du billet d\'origine. Mode REPORT : crée un nouveau billet sur un voyage de la même ligne (tronçon et prix conservés). Mode ANNULATION : annule et rembourse le billet.',
                security: [['bearerAuth' => []]]
            )
        ),
    ],
    openapi: new Operation(
        security: [['bearerAuth' => []]]
    )
)]
#[ApiFilter(SearchFilter::class, properties: [
    'codeticket' => 'partial',
    'voyage.id' => 'exact', /*
        - Le filtre exact sur la relation '?voyage=/api/voyages/5' mais on peut s'en passer vu qu'on a le 'read:Ticket'
    */
    'statut' => 'exact', // VALIDE | REPORTE | ANNULE — pour filtrer l'historique des désistements
])]
#[ApiFilter(OrderFilter::class, properties: [
    'id',
    'codeticket',
    'prix',
    'createdAt'
])]
class Ticket extends EntityBase implements EntrepriseOwnedInterface, LigneGareScopedInterface
{
    public static function ligneScopePath(): array
    {
        return ['voyage', 'ligne']; // billetterie : tickets des voyages dont la ligne dessert sa gare
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['read:Ticket', 'read:Voyage'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'tickets')]
    #[ORM\JoinColumn(nullable: false)] // Sans 'onDelete: 'CASCADE' pour l'historique
    #[Groups(['read:Ticket', 'write:Ticket'])]
    private ?Voyage $voyage = null;

    #[ORM\Column(nullable: true)]
    private ?int $identreprise = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['read:Voyage', 'read:Ticket', 'write:Ticket', 'write:Ticket:update'])]
    private ?string $nomclient = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['read:Voyage', 'read:Ticket', 'write:Ticket', 'write:Ticket:update'])]
    private ?string $contactclient = null;

    #[ORM\Column]
    #[Groups(['read:Voyage', 'read:Ticket'])]
    private ?int $prix = null;

    #[ORM\Column(length: 255)]
    #[Groups(['read:Voyage', 'read:Ticket'])]
    private ?string $codeticket = null;

    #[ORM\ManyToOne(inversedBy: 'tickets')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['read:Ticket', 'read:Voyage', 'write:Ticket'])]
    private ?Siege $siege = null;

    #[ORM\ManyToOne(inversedBy: 'tickets')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['read:Ticket', 'read:Voyage', 'write:Ticket'])]
    private ?Gare $gare = null; // Gare de MONTÉE / émission, on peut le déduire de la gare de l'utilisateur

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['read:Ticket', 'read:Voyage', 'write:Ticket'])]
    private ?Gare $garedescente = null; // Gare de DESCENTE — fixe le prix via la grille globale Tarif(gare, garedescente)

    #[ORM\Column(options: ['default' => 0])]
    #[Groups(['read:Ticket', 'read:Voyage'])]
    private int $remise = 0; // Montant déduit (FCFA), calculé par le processor ; prix = tarif - remise

    #[ORM\ManyToOne]
    #[Groups(['read:Ticket', 'read:Voyage', 'write:Ticket'])]
    private ?Beneficiaire $beneficiaire = null; // Bénéficiaire de la remise (obligatoire si remise > 0)

    // -- Désistement (report / annulation) -- //

    #[ORM\Column(length: 20, options: ['default' => 'VALIDE'])]
    #[Groups(['read:Ticket', 'read:Voyage'])]
    private string $statut = 'VALIDE'; // VALIDE | REPORTE | ANNULE (cf. App\Domain\Enum\TicketStatus)

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)] // Renseigné sur le billet issu d'un REPORT : pointe vers le billet désisté
    #[Groups(['read:Ticket'])]
    private ?Ticket $ticketOrigine = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['read:Ticket'])]
    private ?\DateTimeImmutable $datedesistement = null; // Horodatage du report/annulation

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['read:Ticket'])]
    private ?string $motifdesistement = null;

    /*
        - Entrées TRANSITOIRES (non persistées) : l'agent saisit un type + une valeur,
          le TicketProcessor calcule le montant de la remise et le prix net.
    */
    #[Groups(['write:Ticket'])]
    private ?string $remisetype = null; // 'MONTANT' | 'POURCENTAGE'

    #[Groups(['write:Ticket'])]
    private ?int $remisevaleur = null; // montant en FCFA, ou pourcentage selon remisetype

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVoyage(): ?Voyage
    {
        return $this->voyage;
    }

    public function setVoyage(?Voyage $voyage): static
    {
        $this->voyage = $voyage;

        return $this;
    }

    public function getIdentreprise(): ?int
    {
        return $this->identreprise;
    }

    public function setIdentreprise(?int $identreprise): static
    {
        $this->identreprise = $identreprise;

        return $this;
    }

    public function getNomclient(): ?string
    {
        return $this->nomclient;
    }

    public function setNomclient(?string $nomclient): static
    {
        $this->nomclient = $nomclient;

        return $this;
    }

    public function getContactclient(): ?string
    {
        return $this->contactclient;
    }

    public function setContactclient(?string $contactclient): static
    {
        $this->contactclient = $contactclient;

        return $this;
    }

    public function getPrix(): ?int
    {
        return $this->prix;
    }

    public function setPrix(int $prix): static
    {
        $this->prix = $prix;

        return $this;
    }

    public function getCodeticket(): ?string
    {
        return $this->codeticket;
    }

    public function setCodeticket(string $codeticket): static
    {
        $this->codeticket = $codeticket;

        return $this;
    }

    public function getSiege(): ?Siege
    {
        return $this->siege;
    }

    public function setSiege(?Siege $siege): static
    {
        $this->siege = $siege;

        return $this;
    }

    public function getGare(): ?Gare
    {
        return $this->gare;
    }

    public function setGare(?Gare $gare): static
    {
        $this->gare = $gare;

        return $this;
    }

    public function getGaredescente(): ?Gare
    {
        return $this->garedescente;
    }

    public function setGaredescente(?Gare $garedescente): static
    {
        $this->garedescente = $garedescente;

        return $this;
    }

    public function getRemise(): int
    {
        return $this->remise;
    }

    public function setRemise(int $remise): static
    {
        $this->remise = $remise;

        return $this;
    }

    public function getBeneficiaire(): ?Beneficiaire
    {
        return $this->beneficiaire;
    }

    public function setBeneficiaire(?Beneficiaire $beneficiaire): static
    {
        $this->beneficiaire = $beneficiaire;

        return $this;
    }

    public function getRemisetype(): ?string
    {
        return $this->remisetype;
    }

    public function setRemisetype(?string $remisetype): static
    {
        $this->remisetype = $remisetype;

        return $this;
    }

    public function getRemisevaleur(): ?int
    {
        return $this->remisevaleur;
    }

    public function setRemisevaleur(?int $remisevaleur): static
    {
        $this->remisevaleur = $remisevaleur;

        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getTicketOrigine(): ?Ticket
    {
        return $this->ticketOrigine;
    }

    public function setTicketOrigine(?Ticket $ticketOrigine): static
    {
        $this->ticketOrigine = $ticketOrigine;

        return $this;
    }

    public function getDatedesistement(): ?\DateTimeImmutable
    {
        return $this->datedesistement;
    }

    public function setDatedesistement(?\DateTimeImmutable $datedesistement): static
    {
        $this->datedesistement = $datedesistement;

        return $this;
    }

    public function getMotifdesistement(): ?string
    {
        return $this->motifdesistement;
    }

    public function setMotifdesistement(?string $motifdesistement): static
    {
        $this->motifdesistement = $motifdesistement;

        return $this;
    }
}

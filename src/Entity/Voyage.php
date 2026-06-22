<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use App\Domain\Enum\TicketStatus;
use App\Entity\Dto\AffectcarInput;
use App\Entity\Dto\AffectpersonnelInput;
use App\Entity\Interface\EntrepriseOwnedInterface;
use App\Entity\Interface\LigneGareScopedInterface;
use App\Entity\Interface\HasSoftDeleteGuard;
use App\Entity\Output\Bordereau\BordereauOutput;
use App\Entity\Output\Bordereau\Chauffeur\BordereauChauffeurOutput;
use App\Filter\PersonnelFilter;
use App\Repository\VoyageRepository;
use App\State\AffectcarProcessor;
use App\State\AffectpersonnelProcessor;
use App\State\BordereauChauffeurProvider;
use App\State\BordereauProvider;
use App\State\ReceptionnerVoyageProcessor;
use App\State\SoftDeleteProcessor;
use App\State\VoyageProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: VoyageRepository::class)]
/*
    - L'unicité (datedebut, ligne) est vérifiée dans 'VoyageProcessor' : robuste à la transition
      trajet -> ligne, la ligne étant résolue dans le processor (et non au moment de la validation).
*/
// #[ORM\UniqueConstraint(columns: ['codevoyage', 'identreprise'])] -- Va causé problème à cause du 'deletedAt'
#[ApiResource(
    security: "is_granted('IS_AUTHENTICATED_FULLY')",
    normalizationContext: ['groups' => ['read:Voyage', 'read:Base'], 'skip_null_values' => false],
    denormalizationContext: ['groups' => ['write:Voyage']],
    paginationItemsPerPage: 25,
    paginationClientItemsPerPage: true,
    order: ['createdAt' => 'DESC'],
    operations: [
        new GetCollection(
            security: "is_granted('VOIR', 'Voyage') or is_granted('ROLE_USER')",
            openapi: new Operation(
                summary: 'La liste des voyages',
                description: 'Permet de voir la liste des voyages',
                security: [['bearerAuth' => []]]
            )
        ),
        new Get(
            security: "is_granted('VOIR', object) or is_granted('ROLE_USER')",
            requirements: ['id' => '\d+'],
            openapi: new Operation(
                summary: 'Le voyage',
                description: 'Permet de voir un voyage',
                security: [['bearerAuth' => []]]
            )
        ),
        new Post(
            security: "is_granted('CREER', 'Voyage')",
            processor: VoyageProcessor::class,
            openapi: new Operation(
                summary: 'Permet de créer un voyage',
                description: 'Création du voyage',
                security: [['bearerAuth' => []]]
            )
        ),
        new Patch(
            security: "is_granted('MODIFIER', object)",
            requirements: ['id' => '\d+'],
            processor: VoyageProcessor::class,
            denormalizationContext: ['groups' => ['write:Voyage:update']],
            openapi: new Operation(
                summary: 'Modification du voyage',
                description: 'Permet de modifier un voyage',
                security: [['bearerAuth' => []]]
            )
        ),
        new Patch(
            security: "is_granted('SUPPRIMER', object)",
            uriTemplate: '/voyages/{id}/remove',
            requirements: ['id' => '\d+'],
            input: false,
            processor: SoftDeleteProcessor::class,
            openapi: new Operation(
                summary: 'Mise en corbeille du voyage',
                description: 'Permet de mettre un voyage en corbeille',
                security: [['bearerAuth' => []]]
            )
        ),
        new Patch(
            security: "is_granted('ROLE_USER')",
            uriTemplate: '/voyages/{id}/receptionner',
            requirements: ['id' => '\d+'],
            input: false,
            processor: ReceptionnerVoyageProcessor::class,
            openapi: new Operation(
                summary: 'Réceptionner un voyage à sa gare',
                description: 'L\'agent confirme le passage du véhicule à sa gare : les courriers et bagages qui y descendent sont réceptionnés/livrés automatiquement',
                security: [['bearerAuth' => []]]
            )
        ),
        new Patch(
            security: "is_granted('MODIFIER', object)",
            requirements: ['id' => '\d+'],
            uriTemplate: '/voyages/{id}/car',
            input: AffectcarInput::class,
            processor: AffectcarProcessor::class,
            denormalizationContext: ['groups' => ['write:AffectcarInput']],
            openapi: new Operation(
                summary: 'Affectation d\'un car à un voyage',
                description: 'Permet d\'affecter un car à un voyage',
                security: [['bearerAuth' => []]]
            )
        ),
        new Patch(
            security: "is_granted('MODIFIER', object)",
            requirements: ['id' => '\d+'],
            uriTemplate: '/voyages/{id}/personnel',
            input: AffectpersonnelInput::class,
            processor: AffectpersonnelProcessor::class,
            name: 'Affect-voyage',
            denormalizationContext: ['groups' => ['write:AffectpersonnelInput']],
            openapi: new Operation(
                summary: 'Affectation d\'un personnel à un voyage',
                description: 'Permet d\'affecter un personnel à un voyage',
                security: [['bearerAuth' => []]]
            )
        ),
        new Get(
            uriTemplate: '/voyages/{id}/bordereau',
            uriVariables: [
                'id' => new Link(fromClass: Voyage::class)
            ],
            security: "is_granted('VOIR', 'Voyage')",
            provider: BordereauProvider::class,
            output: BordereauOutput::class,
            normalizationContext: ['groups' => []], /*
                - Pour qu'il normalize le output sans utilisé le groupe
            */
            openapi: new Operation(
                summary: 'Bordereau d\'un voyage par gare',
                security: [['bearerAuth' => []]]
            )
        ),
        new Get(
            uriTemplate: '/voyages/{id}/bordereau/chauffeur',
            requirements: ['id' => '\d+'],
            security: "is_granted('VOIR', 'Voyage')",
            provider: BordereauChauffeurProvider::class,
            output: BordereauChauffeurOutput::class,
            normalizationContext: ['groups' => []],
            openapi: new Operation(
                summary: 'Bordereau chauffeur',
                description: 'Document de transfert chauffeur → gare d\'arrivée',
                security: [['bearerAuth' => []]]
            )
        ),
    ],
    openapi: new Operation(
        security: [['bearerAuth' => []]]
    )
)]
#[ApiFilter(SearchFilter::class, properties: [
    'codevoyage' => 'partial',
    'ligne.id' => 'exact',
    'car.id' => 'exact',
    'datefin' => 'exact'
])]
#[ApiFilter(OrderFilter::class, properties: [
    'id',
    'datedebut',
    'provenance',
    'destination',
    'placestotal',
    'createdAt'
])]
#[ApiFilter(DateFilter::class, properties: ['datedebut'])]
#[ApiFilter(ExistsFilter::class, properties: ['datefin'])] /* Pour récupérer que les voyages en cours
    - Vu que 'mysql' ne comprend pas 'null' comme une valeur 'DATETIME' valide et va l'interprèté 'WHERE datefin = 'null'' on a le 'ExistsFilter' qui lui 'datefin IS NULL' mais attend '?exists[datefin]=false'
*/
#[ApiFilter(PersonnelFilter::class)] /*
    - On.. filtre qui fais un join sur 'Detailpersonnel' via 'personnel.id' pour récupérer les voyages du personnel
*/
class Voyage extends EntityBase implements EntrepriseOwnedInterface, HasSoftDeleteGuard, LigneGareScopedInterface
{
    public static function ligneScopePath(): array
    {
        return ['ligne'];
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['read:Voyage', 'read:Personnel', 'read:Ticket', 'read:Courrier', 'read:Bagage'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['read:Voyage', 'read:Personnel', 'read:Ticket', 'read:Courrier', 'read:Bagage'])]
    private ?string $codevoyage = null;

    #[ORM\Column(length: 255)]
    #[Groups(['read:Voyage', 'write:Voyage', 'write:Voyage:update', 'read:Personnel', 'read:Ticket', 'read:Bagage'])]
    private ?string $provenance = null;

    #[ORM\Column(length: 255)]
    #[Groups(['read:Voyage', 'write:Voyage', 'read:Personnel', 'read:Ticket', 'write:Voyage:update', 'read:Bagage'])]
    private ?string $destination = null;

    #[ORM\Column]
    #[Groups(['read:Voyage', 'write:Voyage', 'read:Personnel', 'read:Ticket', 'write:Voyage:update'])]
    private ?\DateTimeImmutable $datedebut = null; // 'date départ'

    #[ORM\Column(nullable: true)]
    #[Groups(['read:Voyage', 'write:Voyage', 'write:Voyage:update', 'read:Personnel', 'read:Ticket'])]
    private ?\DateTimeImmutable $datefin = null;

    #[ORM\ManyToOne(inversedBy: 'voyages')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['read:Voyage', 'write:Voyage', 'read:Ticket'])]
    private ?Ligne $ligne = null;

    #[ORM\ManyToOne(inversedBy: 'voyages')]
    // #[ORM\JoinColumn(nullable: false)]  -- onDelete: 'RESTRICT'
    #[Groups(['read:Voyage', 'write:Voyage', 'write:Voyage:update', 'read:Personnel'])] // 'optionel' ou l'ajouter à partir d'un 'input' et '..update' car au cours d'un voyage on peut changer un car
    private ?Car $car = null;

    #[ORM\Column(nullable: true)]
    private ?int $identreprise = null;

    /**
     * @var Collection<int, Detailpersonnel>
     */
    #[ORM\OneToMany(targetEntity: Detailpersonnel::class, mappedBy: 'voyage')]
    #[Groups(['read:Voyage'])]
    private Collection $detailpersonnels;

    /**
     * @var Collection<int, Ticket>
     */
    #[ORM\OneToMany(targetEntity: Ticket::class, mappedBy: 'voyage')]
    #[Groups(['read:Voyage'])]
    private Collection $tickets;

    #[ORM\Column(nullable: true)]
    #[Groups(['read:Voyage'])]
    // #[Assert\PositiveOrZero]
    private ?int $placestotal = null;

    /**
     * @var Collection<int, Courrier>
     */
    #[ORM\OneToMany(targetEntity: Courrier::class, mappedBy: 'voyage')]
    #[Groups(['read:Voyage'])]
    private Collection $courriers;

    /**
     * @var Collection<int, Bagage>
     */
    #[ORM\OneToMany(targetEntity: Bagage::class, mappedBy: 'voyage')]
    #[Groups(['read:Voyage'])]
    private Collection $bagages;

    public function __construct()
    {
        $this->detailpersonnels = new ArrayCollection();
        $this->tickets = new ArrayCollection();
        $this->courriers = new ArrayCollection();
        $this->bagages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCodevoyage(): ?string
    {
        return $this->codevoyage;
    }

    public function setCodevoyage(string $codevoyage): static
    {
        $this->codevoyage = $codevoyage;

        return $this;
    }

    public function getProvenance(): ?string
    {
        return $this->provenance;
    }

    public function setProvenance(string $provenance): static
    {
        $this->provenance = $provenance;

        return $this;
    }

    public function getDestination(): ?string
    {
        return $this->destination;
    }

    public function setDestination(string $destination): static
    {
        $this->destination = $destination;

        return $this;
    }

    public function getDatedebut(): ?\DateTimeImmutable
    {
        return $this->datedebut;
    }

    public function setDatedebut(\DateTimeImmutable $datedebut): static
    {
        $this->datedebut = $datedebut;

        return $this;
    }

    public function getDatefin(): ?\DateTimeImmutable
    {
        return $this->datefin;
    }

    public function setDatefin(\DateTimeImmutable $datefin): static
    {
        $this->datefin = $datefin;

        return $this;
    }

    public function getLigne(): ?Ligne
    {
        return $this->ligne;
    }

    public function setLigne(?Ligne $ligne): static
    {
        $this->ligne = $ligne;

        return $this;
    }

    public function getCar(): ?Car
    {
        return $this->car;
    }

    public function setCar(?Car $car): static
    {
        $this->car = $car;

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

    /**
     * @return Collection<int, Detailpersonnel>
     */
    public function getDetailpersonnels(): Collection
    {
        return $this->detailpersonnels;
    }

    public function addDetailpersonnel(Detailpersonnel $detailpersonnel): static
    {
        if (!$this->detailpersonnels->contains($detailpersonnel)) {
            $this->detailpersonnels->add($detailpersonnel);
            $detailpersonnel->setVoyage($this);
        }

        return $this;
    }

    public function removeDetailpersonnel(Detailpersonnel $detailpersonnel): static
    {
        if ($this->detailpersonnels->removeElement($detailpersonnel)) {
            // set the owning side to null (unless already changed)
            if ($detailpersonnel->getVoyage() === $this) {
                $detailpersonnel->setVoyage(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Ticket>
     */
    public function getTickets(): Collection
    {
        return $this->tickets;
    }

    public function addTicket(Ticket $ticket): static
    {
        if (!$this->tickets->contains($ticket)) {
            $this->tickets->add($ticket);
            $ticket->setVoyage($this);
        }

        return $this;
    }

    public function removeTicket(Ticket $ticket): static
    {
        if ($this->tickets->removeElement($ticket)) {
            // set the owning side to null (unless already changed)
            if ($ticket->getVoyage() === $this) {
                $ticket->setVoyage(null);
            }
        }

        return $this;
    }

    public function getPlacesTotal(): ?int
    {
        return $this->placestotal;
    }

    public function setPlacesTotal(?int $places_total): static
    {
        $this->placestotal = $places_total;

        return $this;
    }

    /* Nombre de billets ACTIFS (VALIDE, deletedAt IS NULL) vendus sur le voyage — remplace l'ancien compteur
       stocké 'placesoccupees'. Les billets reportés/annulés (désistements) ne comptent pas. Informatif : avec la
       vente PAR TRONÇON ce total peut dépasser 'placestotal' (un même siège est revendable sur des tronçons
       disjoints). La dispo réelle par tronçon = 'SiegeStateProvider'.
     */
    #[Groups(['read:Voyage'])]
    public function getTicketsCount(): int
    {
        return $this->tickets->filter(
            fn(Ticket $t) => $t->getDeletedAt() === null && $t->getStatut() === TicketStatus::STATUT_VALIDE->value
        )->count();
    }

    public function getSoftDeleteBlockers(): array
    {
        $errors = [];

        // Seuls les billets VALIDE bloquent la suppression ; les billets désistés sont de l'historique
        $ticketsNotDeleted = $this->tickets->filter(
            fn(Ticket $v) => $v->getDeletedAt() === null && $v->getStatut() === TicketStatus::STATUT_VALIDE->value
        );

        if(!$ticketsNotDeleted->isEmpty()) {
            $errors[] = sprintf(
                'Le voyage est liée à %d tickets(s) actif(s).',
                $ticketsNotDeleted->count()
            );
        }

        return $errors;
    }

    /**
     * @return Collection<int, Courrier>
     */
    public function getCourriers(): Collection
    {
        return $this->courriers;
    }

    public function addCourrier(Courrier $courrier): static
    {
        if (!$this->courriers->contains($courrier)) {
            $this->courriers->add($courrier);
            $courrier->setVoyage($this);
        }

        return $this;
    }

    public function removeCourrier(Courrier $courrier): static
    {
        if ($this->courriers->removeElement($courrier)) {
            // set the owning side to null (unless already changed)
            if ($courrier->getVoyage() === $this) {
                $courrier->setVoyage(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Bagage>
     */
    public function getBagages(): Collection
    {
        return $this->bagages;
    }

    public function addBagage(Bagage $bagage): static
    {
        if (!$this->bagages->contains($bagage)) {
            $this->bagages->add($bagage);
            $bagage->setVoyage($this);
        }

        return $this;
    }

    public function removeBagage(Bagage $bagage): static
    {
        if ($this->bagages->removeElement($bagage)) {
            // set the owning side to null (unless already changed)
            if ($bagage->getVoyage() === $this) {
                $bagage->setVoyage(null);
            }
        }

        return $this;
    }

}

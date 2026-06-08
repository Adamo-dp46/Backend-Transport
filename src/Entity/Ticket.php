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
use App\Entity\Interface\EntrepriseOwnedInterface;
use App\Repository\TicketRepository;
use App\State\SoftDeleteProcessor;
use App\State\TicketProcessor;
use App\State\UpdatedbyProcessor;
use App\Validator\UniquePerEntreprise;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: TicketRepository::class)]
#[UniquePerEntreprise(
    fields: ['voyage', 'siege'],
    message: 'Le ticket est déjà payé pour le voyage'
)]
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
            security: "is_granted('VOIR', object)",
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
])]
#[ApiFilter(OrderFilter::class, properties: [
    'id',
    'codeticket',
    'prix',
    'createdAt'
])]
class Ticket extends EntityBase implements EntrepriseOwnedInterface
{
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
    private ?Gare $gare = null;

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
}

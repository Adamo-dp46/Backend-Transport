<?php

namespace App\Entity;

use App\Repository\DetailcourrierRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: DetailcourrierRepository::class)]
/*
    -- Si on veut permettre l'ajout ou la modification d'un colis individuellement après création du courrier
    #[ApiResource(
        security: "is_granted('IS_AUTHENTICATED_FULLY')",
        normalizationContext: ['groups' => ['read:Detailcourrier', 'read:Base']],
        denormalizationContext: ['groups' => ['write:Detailcourrier']],
        paginationItemsPerPage: 25,
        paginationClientItemsPerPage: true,
        operations: [
            new GetCollection(
                security: "is_granted('VOIR', 'Detailcourrier')",
                openapi: new Operation(
                    summary: 'Liste des détails courrier',
                    description: 'Permet de voir la liste des détails courrier',
                    security: [['bearerAuth' => []]]
                )
            ),
            new Get(
                security: "is_granted('VOIR', object)",
                requirements: ['id' => '\d+'],
                openapi: new Operation(
                    summary: 'Un détail courrier',
                    description: 'Permet de voir un détail courrier',
                    security: [['bearerAuth' => []]]
                )
            ),
            new Post(
                security: "is_granted('CREER', 'Detailcourrier')",
                processor: DetailcourrierProcessor::class,
                openapi: new Operation(
                    summary: 'Ajout d\'un colis au courrier',
                    description: 'Permet d\'ajouter un colis à un courrier existant',
                    security: [['bearerAuth' => []]]
                )
            ),
            new Patch(
                security: "is_granted('MODIFIER', object)",
                requirements: ['id' => '\d+'],
                processor: DetailcourrierProcessor::class,
                denormalizationContext: ['groups' => ['write:Detailcourrier:update']],
                openapi: new Operation(
                    summary: 'Modification d\'un colis',
                    description: 'Permet de modifier un colis',
                    security: [['bearerAuth' => []]]
                )
            ),
            new Patch(
                security: "is_granted('SUPPRIMER', object)",
                uriTemplate: '/detailcourriers/{id}/remove',
                requirements: ['id' => '\d+'],
                input: false,
                processor: SoftDeleteProcessor::class,
                openapi: new Operation(
                    summary: 'Suppression d\'un colis',
                    description: 'Permet de supprimer un colis',
                    security: [['bearerAuth' => []]]
                )
            )
        ],
        openapi: new Operation(
            security: [['bearerAuth' => []]]
        )
    )]
    #[ApiFilter(SearchFilter::class, properties: [
        'courrier.id' => 'exact'
    ])]
*/
class Detailcourrier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['read:Detailcourrier', 'read:Courrier'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'detailcourriers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Courrier $courrier = null;

    #[ORM\Column(length: 255)]
    #[Groups(['read:Detailcourrier', 'read:Courrier', 'write:Detailcourrier'])]
    private ?string $nature = null; // ex: document, marchandise, électronique..

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['read:Detailcourrier', 'read:Courrier', 'write:Detailcourrier'])]
    private ?string $designation = null; // La description précise du colis

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['read:Detailcourrier', 'read:Courrier', 'write:Detailcourrier'])]
    private ?string $emballage = null; // ex: Sachet Blanc, Carton

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['read:Detailcourrier', 'read:Courrier', 'write:Detailcourrier'])]
    private ?string $type = null; // ex: FRAGILE, NORMAL, VOLUMINEUX

    #[ORM\Column(nullable: true)]
    #[Groups(['read:Detailcourrier', 'read:Courrier', 'write:Detailcourrier'])]
    private ?int $poids = null;

    #[ORM\Column]
    #[Groups(['read:Detailcourrier', 'read:Courrier', 'write:Detailcourrier'])]
    private ?int $valeur = null; // La base de calcul de la taxe

    #[ORM\Column]
    #[Groups(['read:Detailcourrier', 'read:Courrier'])]
    private ?int $montant = null; // La taxe de ce colis calculée via 'TarifCourrier'

    #[ORM\ManyToOne(inversedBy: 'detailcourriers')]
    #[Groups(['read:Detailcourrier', 'read:Courrier'])]
    private ?Tarifcourrier $tarifcourrier = null; /*
        - On le conserve pour l'historique même si la grille tarifaire change 
    */

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCourrier(): ?Courrier
    {
        return $this->courrier;
    }

    public function setCourrier(?Courrier $courrier): static
    {
        $this->courrier = $courrier;

        return $this;
    }

    public function getNature(): ?string
    {
        return $this->nature;
    }

    public function setNature(string $nature): static
    {
        $this->nature = $nature;

        return $this;
    }

    public function getDesignation(): ?string
    {
        return $this->designation;
    }

    public function setDesignation(?string $designation): static
    {
        $this->designation = $designation;

        return $this;
    }

    public function getEmballage(): ?string
    {
        return $this->emballage;
    }

    public function setEmballage(?string $emballage): static
    {
        $this->emballage = $emballage;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getPoids(): ?int
    {
        return $this->poids;
    }

    public function setPoids(?int $poids): static
    {
        $this->poids = $poids;

        return $this;
    }

    public function getValeur(): ?int
    {
        return $this->valeur;
    }

    public function setValeur(int $valeur): static
    {
        $this->valeur = $valeur;

        return $this;
    }

    public function getMontant(): ?int
    {
        return $this->montant;
    }

    public function setMontant(int $montant): static
    {
        $this->montant = $montant;

        return $this;
    }

    public function getTarifcourrier(): ?Tarifcourrier
    {
        return $this->tarifcourrier;
    }

    public function setTarifcourrier(?Tarifcourrier $tarifcourrier): static
    {
        $this->tarifcourrier = $tarifcourrier;

        return $this;
    }
}

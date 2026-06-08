<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use App\Entity\Dto\TrajetInput;
use App\Entity\Interface\EntrepriseOwnedInterface;
use App\Entity\Interface\HasSoftDeleteGuard;
use App\Repository\TrajetRepository;
use App\State\SoftDeleteProcessor;
use App\State\TrajetProcessor;
use App\State\UpdatedbyProcessor;
use App\Validator\UniquePerEntreprise;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: TrajetRepository::class)]
#[UniquePerEntreprise(
    fields: ['provenance', 'destination'],
    message: 'Le trajet existe déjà pour votre entreprise'
)] /*
    - Ne vas pas fonctionner pour le 'post' vu que j'utilise un 'input' sinon on le fais dans le 'processor'
*/
#[ApiResource(
    security: "is_granted('IS_AUTHENTICATED_FULLY')",
    normalizationContext: ['groups' => ['read:Trajet', 'read:Base'], 'skip_null_values' => false],
    denormalizationContext: ['groups' => ['write:Trajet']],
    paginationEnabled: false,
    order: ['createdAt' => 'DESC'],
    operations: [
        new GetCollection(
            security: "is_granted('VOIR', 'Trajet') or is_granted('ROLE_USER')",
            openapi: new Operation(
                summary: 'Liste des trajets',
                description: 'Permet de voir la liste des trajets',
                security: [['bearerAuth' => []]]
            )
        ),
        new Get(
            security: "is_granted('VOIR', object)",
            requirements: ['id' => '\d+'],
            openapi: new Operation(
                summary: 'Le trajet',
                description: 'Permet de voir un trajet',
                security: [['bearerAuth' => []]]
            )
        ),
        new Post(
            security: "is_granted('CREER', 'Trajet')",
            input: TrajetInput::class,
            processor: TrajetProcessor::class, // On l'a fais à cause du 'codetrajet'
            denormalizationContext: ['groups' => ['write:TrajetInput']],
            openapi: new Operation(
                summary: 'Permet de créer un tarif',
                description: 'Création du tarif',
                security: [['bearerAuth' => []]]
            )
        ),
        new Patch(
            security: "is_granted('MODIFIER', object)",
            requirements: ['id' => '\d+'],
            processor: UpdatedbyProcessor::class,
            openapi: new Operation(
                summary: 'Modification du trajet',
                description: 'Permet de modifier un trajet',
                security: [['bearerAuth' => []]]
            )
        ),
        new Patch(
            security: "is_granted('SUPPRIMER', object)",
            uriTemplate: '/trajets/{id}/remove',
            requirements: ['id' => '\d+'],
            input: false,
            processor: SoftDeleteProcessor::class,
            openapi: new Operation(
                summary: 'Mise en corbeille du trajet',
                description: 'Permet de mettre un trajet en corbeille',
                security: [['bearerAuth' => []]]
            )
        ),
    ],
    openapi: new Operation(
        security: [['bearerAuth' => []]]
    )
)]
class Trajet extends EntityBase implements EntrepriseOwnedInterface, HasSoftDeleteGuard
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['read:Trajet', 'read:Voyage'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['read:Trajet', 'read:Voyage'])]
    private ?string $codetrajet = null;

    #[ORM\Column(length: 255)]
    #[Groups(['read:Trajet', 'read:Voyage'])]
    private ?string $provenance = null; // Ou.. une relation 'gare_depart'

    #[ORM\Column(length: 255)]
    #[Groups(['read:Trajet', 'read:Voyage'])]
    private ?string $destination = null; // Ou.. !! 'gare_arrivee

    #[ORM\Column(nullable: true)]
    #[Groups(['read:Trajet'])]
    private ?int $orderindex = null;

    #[ORM\ManyToOne(inversedBy: 'trajets')]
    #[ORM\JoinColumn(nullable: false)] // onDelete: 'RESTRICT'
    #[Groups(['read:Trajet', 'write:Trajet'])]
    private ?Tarif $tarif = null;

    #[ORM\Column(nullable: true)]
    private ?int $identreprise = null;

    /**
     * @var Collection<int, Voyage>
     */
    #[ORM\OneToMany(targetEntity: Voyage::class, mappedBy: 'trajet')]
    private Collection $voyages;

    public function __construct()
    {
        $this->voyages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCodetrajet(): ?string
    {
        return $this->codetrajet;
    }

    public function setCodetrajet(string $codetrajet): static
    {
        $this->codetrajet = $codetrajet;

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

    public function getOrderindex(): ?int
    {
        return $this->orderindex;
    }

    public function setOrderindex(int $orderindex): static
    {
        $this->orderindex = $orderindex;

        return $this;
    }

    public function getTarif(): ?Tarif
    {
        return $this->tarif;
    }

    public function setTarif(?Tarif $tarif): static
    {
        $this->tarif = $tarif;

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
     * @return Collection<int, Voyage>
     */
    public function getVoyages(): Collection
    {
        return $this->voyages;
    }

    public function addVoyage(Voyage $voyage): static
    {
        if (!$this->voyages->contains($voyage)) {
            $this->voyages->add($voyage);
            $voyage->setTrajet($this);
        }

        return $this;
    }

    public function removeVoyage(Voyage $voyage): static
    {
        if ($this->voyages->removeElement($voyage)) {
            // set the owning side to null (unless already changed)
            if ($voyage->getTrajet() === $this) {
                $voyage->setTrajet(null);
            }
        }

        return $this;
    }

    public function getSoftDeleteBlockers(): array
    {
        $errors = [];

        $voyagesNotDeleted = $this->voyages->filter(
            fn(Voyage $v) => $v->getDeletedAt() === null
        );

        if(!$voyagesNotDeleted->isEmpty()) {
            $errors[] = sprintf(
                'Le trajet est liée à %d voyage(s) actif(s).',
                $voyagesNotDeleted->count()
            );
        }

        return $errors;
    }

    /* Le count collection et l'avantage est que 'Doctrine' ne charge pas la collection mais 'COUNT(*)'
     */
    #[Groups(['read:Trajet'])]
    public function getVoyagesCount(): int
    {
        return $this->voyages->count();
    }

}

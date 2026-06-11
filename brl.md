### Brl

- **Command**
    > php -S localhost:8000 -t public | symfony serve
    > php bin/console cache:clear
    > php bin/console debug:router
    > php bin/console make:controller
    > php bin/console make:entity
    > php bin/console make:voter
    > php bin/console make:listener
    > php bin/console make:subscriber
    > php bin/console make:fixtures
    > php bin/console doctrine:fixtures:load
    > php bin/console make:migration
    > php bin/console doctrine:migrations:migrate
    > php bin/console doctrine:schema:update --force : `--env=test` pour les tests
        > php bin/console doctrine:schema:update --dump-sql
    > php bin/console doctrine:fixtures:load : `--env=test` pour les tests
    > php bin/console translation:extract --dump-messages fr
    > php bin/console translation:extract --force fr --format=yaml
    > php bin/console make:test
    > php bin/console make:state-processor
    > php bin/console make:state-provider
- 

- 
- On.. un bypass à `ROLE_ADMIN_GARE` pour éviter de lui donner des rôles manuellement et on.. son périmètre via les extensions et processors



Vision plus moderne

Tu peux aller vers :

Réservation en ligne

Les passagers réservent via mobile.

QR Code sur billets

Validation rapide à l’embarquement.

Géolocalisation

Suivi des cars en temps réel.

Notifications

SMS :

départ imminent
retard
arrivée

8. Réductions possibles

Certaines gares gèrent :

enfants,
étudiants,
abonnés,
clients fidèles.

Exemple :

étudiant = -10%
enfant = demi-tarif


Réservation temporaire

Blocage de siège avant paiement.



Pour le filtre multi-gare on vas le faire plutard car il prend en compte d'autres notion donc on peut attaquer l'étape 3
 car des gares d'un même trajet doivent pouvoir partager des données




- - 
Dans un départ un client peut déscendre en route donc on doit avoir la possibilité de revendre un siège
Comment on gère le grisage des sièges


Si gare voit grisés uniquement les sièges qu'elle a elle-même vendus. Mais dans ce cas, comment le commercial à bord sait quels sièges sont physiquement occupés quand il est entre deux gares ?

Un siège est occupé entre la gare d'embarquement et la gare de descente du client, donc un ticket doit porter **deux informations** : `gare_embarquement` et `gare_descente`.


- A chaque gare intermédiare voilà comment il est arrivée et voilà comment il est parti - bordereau
- - 



Tarif sur voyage au lieu de trajet ça résoud le problème


Sur ticket
    A => D
    départ: 08H
    siège: 44
    montant: 5000F


















- Les moyens de paiement ou règlement pour les tickets, courrier ou bagages pour savoir par quoi les clients paient le plus souvent
    > Sur la page de création de ticket :: Mobile money -> Ouvre panneau -> Mtn, Wave.. -> puis on saisi l'indentifiant du paiement dans la base de données
- Lien vers les ressources de select sur la page de ceux qui en besoin
- Select | Create User et List User
    > Présélection + sur les champs select parente, crée et le présélectionne dans le champ
- Un matricule pour les utilisateurs ayant vendu le ticket, enregistré bagage et courrier 
- Courrier: séparer leur chiffre d'affaire avec celle de la société
- Gérer le bordereau gare : /api/voyages/1/bordereau?gare=2

- Ticket
    - Code QR sur les tickets
    - Un champ remise ex:1000 sur ticket,  :: on doit le prixvendu et béfinicière
        - contactbeneficiaire
    - Réduction dans ticket
        - Beneficiaire -> soit on le crée ou on le choisi avec le select..
            - Peut être un client qui vient toujours ou un corsaire(celui qui envoi le client) reçois identreprise et visible dans toutes les gares
    - Carte de fidélité pour les meilleurs clients basé sur le numéro du client

- Reservation de ticket, une personne peut rester chez lui à la maison et réservé un ticket ..puis le tire en ligne(perso)
    - Application mobile pour les clients Réservation de tickets voir les départs, scanner un code qr (comportant toutes les informations du ticket)

- Place: de la droite vers la gauche et 6 places derrière
    3 4 5 2 1
    6 places
    - Vert: libre au départ, vendu: rouge, libérer à nouveau gris, ainsi de suite

On vas gérer les dépenses : 2 types (Dépense générale et gare)
    Objetdepense -> libelle          Objetdepensegare..
    Depense                          Depensegare..
        objetdepense -> vers Objetdepense
        date
        montant
        detail

Table Ville






1. Ce que tu décris réellement

Tu as :

Ligne :
Abidjan → Korhogo

Mais dans cette ligne :

des passagers descendent à Yamoussoukro,
d’autres à Bouaké,
d’autres à Korhogo.

Donc :

un seul voyage,
plusieurs destinations possibles,
plusieurs tarifs.




Le gros défi métier : gestion des sièges

C’est LE vrai problème maintenant.

Exemple :

siège 12 occupé jusqu’à Bouaké, puis libre après Bouaké.
Le siège devient libre après l’arrêt

Donc :
un même siège peut être revendu après un arrêt.












6. Pourquoi cette structure est excellente

Parce qu’elle gère :

✅ multi-destinations
✅ montées/descentes intermédiaires
✅ revente des sièges
✅ lignes longues distances
✅ extensions futures









- Lors de la création d'un trajet on vas liés les gares qui le conçerne, dans un trajet on a la gare de départ, la gare de destination et les gares intermédiares et on peut aussi les rangés par ordre
    - Les utilisateurs d'une gare ne peuvent voir que les données des gares qui sont sur le même trajet que leur gare vu que des informations sont partagés entre des gares => C'est ici que le filtre de multi-gare va s'appliquer
    - Une gare ne peut pas vendre un ticket d'un voyage qui n'est sur son trajet

Une gare intermédiaire ne peut pas vendre les tickets de la gare précédente ou de provenance.. je me dis aussi que c'est pas nécéssaire vu qu'il ne voit pas la carte de la gare de provenance

- Les gares intermédiares du trajet ne clôture pas le voyage mais receptionne, si une gare intermédiaire fais receptionné sur le voyage ça dégrise les sièges des clients qui doivent décendre à cette gare pour la gare de provenance
    > Mais le commercial pose problème, comment sa carte s'actualise s'il est dans une gare intermédiaire lorsqu'il vont vendre sièges

- Propose moi une solution propre et cohérent pour la partie dans laquelle on grise les sièges vendu :

    - Dans la partie création de ticket, le faite de grisés les sièges en cas de vente ne conçernent que la gare qui vend, si le client ne décend pas à une gare intermédiaire ex: gare 2 elle doit voir les sièges grisé, les autres gares du même trajet peuvent vendre et verront en grisés les sièges qu'ils ont vendu
        > le problème : Tu dis que chaque gare voit grisés uniquement les sièges qu'elle a elle-même vendus. Mais dans ce cas, comment le commercial à bord sait quels sièges sont physiquement occupés quand il est entre deux gares ?


- Ex: Si la gare 1 vend 30/64, la gare 2 peut vendre les 64 places du départ sans tenir compte des places vendu à la gare précédente, le car arrive à la gare 2 des clients vont décendre et les autres montes au cas ou c'est rempli la gare 2 programme un autre départ avec un autre car pour les clients restants, vend les tickets et devient la gare de provenance du nouveau départ

**Point 1 — La gare intermédiaire et les places.**
Tu dis que la gare 2 peut vendre les 64 places sans tenir compte des 30 vendues en gare 1. C'est parce que les 30 clients de la gare 1 descendent obligatoirement à la gare 2 ? Ou c'est parce que certains clients de la gare 1 peuvent aller au-delà de la gare 2 (gare 3, destination finale) ?

Vu que certains clients de la gare 1 peuvent aller au-delà de la gare 2, on doit empêcher que deux clients occupe le même siège donc je me dis qu'une solution est de grisé un siège selon la gare de destination du client pour toutes les gares du trajet



- Dans un départ(voyage) on un chauffeur et un commercial, le commercial est lié à la gare de provenance, le commercial peut vendre les tickets en route via un TPE dans le cas ou un client monte en route, si un client déscend et qu'un autre monte le commercial va appeller l'administrateur de la gare de provenance pour lui demander de dégrisé le siège pour qu'il le revend
- Des clients peuvent descendre dans les gares intermédiares ou en cours de chemin du départ(voyage) et d'autres peuvent monter pour prendre leur place donc on doit pouvoir revendre un siège plusieurs fois


C'est a peu près la logique qu'on m'a expliquer, analyse bien pour être cohérent

On vas aller étape par étape et liste par quoi on devrait commencer














- - 
Bonne question sur les stats. Voici le raisonnement :

**Soft delete seul** — le ticket disparaît des requêtes sans `deletedAt IS NULL`, donc les stats l'excluent automatiquement. ✅ Simple mais tu perds la distinction entre "supprimé" et "annulé".

**Statut ANNULE seul** — le ticket reste visible partout, les stats doivent explicitement filtrer `statut != ANNULE`. ✅ Traçabilité complète mais plus de travail dans chaque requête.

**Les deux** — le ticket a `statut = ANNULE` ET `deletedAt` rempli. Les stats existantes basées sur `deletedAt IS NULL` l'excluent automatiquement sans modification, ET tu gardes la trace de l'annulation via le statut. C'est le plus adapté à ton cas.

---

### Implémentation complète

**1 — Ajouter `statut` sur `Ticket`**

```php
// Ticket.php
public const STATUTS = ['ACTIF', 'ANNULE'];

#[ORM\Column(length: 20, options: ['default' => 'ACTIF'])]
#[Groups(['read:Ticket', 'read:Voyage'])]
private string $statut = 'ACTIF';

public function getStatut(): string { return $this->statut; }
public function setStatut(string $statut): static { $this->statut = $statut; return $this; }
```

Lance `make:migration` + `doctrine:migrations:migrate`.

---

**2 — Opération d'annulation sur `Ticket`**

```php
// Dans l'entité Ticket — ajouter l'opération
new Patch(
    uriTemplate: '/tickets/{id}/annuler',
    requirements: ['id' => '\d+'],
    security: "is_granted('TICKET_SUPPRIMER', object)",
    input: false,
    processor: AnnulerTicketProcessor::class,
    openapi: new Operation(
        summary: 'Annuler un ticket',
        security: [['bearerAuth' => []]]
    )
),
```

---

**3 — `AnnulerTicketProcessor`**

```php
<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Ticket;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AnnulerTicketProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface     $processor,
        private Security               $security,
        private EntityManagerInterface $em,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        /** @var Ticket $data */
        /** @var User $user */
        $user = $this->security->getUser();

        // Déjà annulé
        if ($data->getStatut() === 'ANNULE') {
            throw new BadRequestHttpException('Ce ticket est déjà annulé');
        }

        // Voyage clôturé
        if ($data->getVoyage()?->getDatefin() !== null) {
            throw new BadRequestHttpException(
                'Impossible d\'annuler un ticket sur un voyage déjà clôturé'
            );
        }

        // Marquer comme annulé + soft delete
        $data->setStatut('ANNULE');
        $data->setDeletedAt(new \DateTimeImmutable());
        $data->setUpdatedBy($user->getId());

        // Décrémenter placesoccupees du voyage
        $voyage = $data->getVoyage();
        if ($voyage && $voyage->getPlacesOccupees() > 0) {
            $voyage->setPlacesOccupees($voyage->getPlacesOccupees() - 1);
            $this->em->persist($voyage);
        }

        return $this->processor->process($data, $operation, $uriVariables, $context);
    }
}
```

---

**4 — `TicketController` — action `annuler`**

```php
#[Route('/{id}/annuler', name: 'annuler', methods: ['POST'], requirements: ['id' => '\d+'])]
#[IsGranted('TICKET_SUPPRIMER')]
public function annuler(int $id, Request $request): Response
{
    try {
        $this->api->patch("/api/tickets/{$id}/annuler", []);
        $this->addFlash('success', 'Ticket annulé avec succès');
    } catch (ApiException $e) {
        $response = $this->apiExceptionHandler->handle($e, null, 'ticket.show', ['id' => $id]);
        if ($response) return $response;
    }

    return $this->redirectToRoute('ticket.show', ['id' => $id]);
}
```

---

**5 — Bouton d'annulation dans `ticket/show.html.twig`**

```twig
{% if is_granted('TICKET_SUPPRIMER') and ticket.statut == 'ACTIF' and not ticket.voyage.datefin %}
<form
    method="POST"
    action="{{ path('ticket.annuler', { id: ticket.id }) }}"
    onsubmit="return confirm('Confirmer l\'annulation de ce ticket ? Cette action est irréversible.')"
>
    <button type="submit" class="btn btn-destructive btn-sm gap-1.5">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
        Annuler le ticket
    </button>
</form>
{% endif %}

{# Badge statut sur la fiche #}
{% if ticket.statut == 'ANNULE' %}
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">
    Annulé
</span>
{% endif %}
```

---

**6 — Bouton dans la liste `ticket/index.html.twig` via le composant React**

Dans `buildColumns` de ton `TicketTable.tsx`, ajouter dans le menu actions :

```tsx
{canDelete && ticket.statut === 'ACTIF' && !ticket.voyage?.datefin && (
    <>
        <DropdownMenuSeparator />
        <DropdownMenuItem asChild>
            <form
                method="POST"
                action={`/ticket/${ticket.id}/annuler`}
                onSubmit={(e) => {
                    if (!confirm("Confirmer l'annulation de ce ticket ?")) {
                        e.preventDefault()
                    }
                }}
            >
                <button
                    type="submit"
                    className="w-full text-left text-red-600 focus:text-red-700"
                >
                    Annuler le ticket
                </button>
            </form>
        </DropdownMenuItem>
    </>
)}
```

---

### Récap de ce que ça fait

Quand un ticket est annulé — `statut = ANNULE` + `deletedAt` rempli. Les stats existantes basées sur `deletedAt IS NULL` l'excluent automatiquement sans aucune modification. Le siège redevient libre immédiatement car `SiegeStateProvider` filtre déjà les tickets avec `deletedAt IS NULL`. `placesoccupees` du voyage est décrémenté atomiquement dans le processor.

Deux conditions bloquantes : permission `TICKET_SUPPRIMER` + voyage non clôturé — les deux vérifiées côté Backend dans le processor ET côté Frontend pour masquer le bouton.






Ah je comprends maintenant — tes stats comptent **tous** les tickets y compris les supprimés, pour avoir l'historique réel des ventes. Donc si tu mets `deletedAt` sur l'annulation, le ticket sera quand même compté dans les stats comme vendu, ce qui fausse les chiffres.

Dans ce cas **statut ANNULE seul** est la bonne approche — pas de `deletedAt`. Le ticket reste visible en base, les stats filtrent explicitement sur `statut = 'ACTIF'` ou excluent `statut = 'ANNULE'`.

---

### Ce qui change

**`AnnulerTicketProcessor`** — supprimer le `deletedAt` :

```php
// ❌ Supprimer
$data->setDeletedAt(new \DateTimeImmutable());

// ✅ Uniquement le statut
$data->setStatut('ANNULE');
$data->setUpdatedBy($user->getId());

// Décrémenter placesoccupees
$voyage = $data->getVoyage();
if ($voyage && $voyage->getPlacesOccupees() > 0) {
    $voyage->setPlacesOccupees($voyage->getPlacesOccupees() - 1);
    $this->em->persist($voyage);
}
```

---

### Adapter les requêtes stats

Partout dans tes providers/repositories de stats où tu comptes les tickets, ajouter le filtre `statut = 'ACTIF'` :

```sql
-- Avant
WHERE t.deleted_at IS NULL

-- Après
WHERE t.deleted_at IS NULL AND t.statut = 'ACTIF'
```

---

### Adapter `SiegeStateProvider`

Le siège doit redevenir libre quand le ticket est annulé — filtrer sur `statut = 'ACTIF'` :

```php
// Dans SiegeStateProvider
$ticketsOccupes = $this->em->getRepository(Ticket::class)
    ->findBy([
        'voyage'    => $voyageId,
        'statut'    => 'ACTIF',    // ← ajouter
        'deletedAt' => null,
    ]);
```

---

### Adapter `TicketProcessor` — bloquer la vente sur un siège annulé

Si quelqu'un essaie de racheter un siège dont le ticket a été annulé, il doit pouvoir le faire — le siège est libre. La vérification existante cherche déjà les tickets `ACTIF` donc c'est bon si tu as bien filtré sur `statut`.





Bonne question. Voici ce que ça impacte :

---

### Méthodes impactées dans `TicketRepository`

Toutes les méthodes qui agrègent des données sur les tickets doivent exclure les tickets annulés avec `t.statut = 'ACTIF'` :

- `countTotal` — sinon les tickets annulés gonflent le compteur
- `recettesTotales` — sinon la recette inclut des montants annulés
- `recettesParJour` — idem
- `detailParAgentEtVoyage` — idem
- `detailParJourEtVoyage` — idem
- `recettesParTrajet` — idem
- `recettesParCar` — idem
- `findByVoyage` — le bordereau chauffeur ne doit pas lister les tickets annulés
- `findBordereauStats` et `findPassagers` — le bordereau gare idem

---

### Correction à appliquer partout

Ajouter sur chaque query :

```php
->andWhere('t.statut = :statut')
->setParameter('statut', 'ACTIF')
```

---

### Ce qui n'est pas impacté

- `placesoccupees` sur `Voyage` — si tu gères la décrémentation à l'annulation côté processor, c'est déjà cohérent
- Les stats Bagage et Courrier — pas de statut ACTIF sur ces entités, leur annulation passe par leurs propres statuts déjà filtrés

---

### Impact sur le `Voyage`

Si `placesoccupees` est décrémenté à l'annulation du ticket, le taux de remplissage dans `ExploitationStatsProvider` reste correct puisqu'il se base sur `v.placesoccupees`. Sinon il faudra recalculer depuis les tickets actifs uniquement.

Tu décrémentes `placesoccupees` à l'annulation ?








Parfait. Voici l'architecture complète.

---

### Ce qui se passe lors d'un désistement

```
Ticket original (ACTIF, voyage A, siège 5)
    └── statut → DESISTE
    └── placesoccupees voyage A → -1
    └── siège 5 du voyage A → LIBRE

Nouveau ticket (ACTIF, voyage B, siège X)
    └── nouveau codeticket
    └── ticketOrigine → référence vers l'ancien ticket
    └── placesoccupees voyage B → +1
    └── prix → tarif du voyage B (peut différer)
```

---

### 1 — Ajouter les champs sur `Ticket`

```php
// Ticket.php

public const STATUTS = ['ACTIF', 'ANNULE', 'DESISTE'];

// Référence vers le ticket d'origine (si ce ticket est issu d'un report)
#[ORM\ManyToOne(targetEntity: self::class)]
#[ORM\JoinColumn(nullable: true)]
#[Groups(['read:Ticket'])]
private ?self $ticketOrigine = null;

// Référence vers le ticket de report (si ce ticket a été reporté)
#[ORM\OneToOne(targetEntity: self::class)]
#[ORM\JoinColumn(nullable: true)]
#[Groups(['read:Ticket'])]
private ?self $ticketReport = null;

public function getTicketOrigine(): ?self { return $this->ticketOrigine; }
public function setTicketOrigine(?self $t): static { $this->ticketOrigine = $t; return $this; }
public function getTicketReport(): ?self { return $this->ticketReport; }
public function setTicketReport(?self $t): static { $this->ticketReport = $t; return $this; }
```

Lance `make:migration` + `doctrine:migrations:migrate`.

---

### 2 — DTO input pour le désistement

```php
<?php

namespace App\Dto;

use App\Entity\Gare;
use App\Entity\Siege;
use App\Entity\Voyage;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class DesistementInput
{
    #[Assert\NotNull(message: 'Le nouveau voyage est obligatoire')]
    #[Groups(['write:Desistement'])]
    public ?Voyage $nouveauVoyage = null;

    #[Assert\NotNull(message: 'Le nouveau siège est obligatoire')]
    #[Groups(['write:Desistement'])]
    public ?Siege $nouveauSiege = null;

    #[Assert\NotNull(message: 'La gare est obligatoire')]
    #[Groups(['write:Desistement'])]
    public ?Gare $gare = null;
}
```

---

### 3 — Opération sur `Ticket`

```php
new Post(
    uriTemplate: '/tickets/{id}/desistement',
    uriVariables: ['id' => new Link(fromClass: Ticket::class)],
    requirements: ['id' => '\d+'],
    security: "is_granted('TICKET_MODIFIER', object)",
    input: DesistementInput::class,
    output: Ticket::class,
    processor: DesistementProcessor::class,
    openapi: new Operation(
        summary: 'Désistement — annule et reporte un ticket sur un nouveau voyage',
        security: [['bearerAuth' => []]]
    )
),
```

---

### 4 — `DesistementProcessor`

```php
<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\DesistementInput;
use App\Entity\Ticket;
use App\Entity\User;
use App\Repository\TicketRepository;
use App\Repository\TarifRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class DesistementProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface     $processor,
        private Security               $security,
        private EntityManagerInterface $em,
        private TicketRepository       $ticketRepository,
        private TarifRepository        $tarifRepository,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Ticket
    {
        /** @var DesistementInput $data */
        /** @var User $user */
        $user         = $this->security->getUser();
        $identreprise = $user->getEntreprise()->getId();

        // Charger le ticket original
        $ticketOriginal = $this->em->getRepository(Ticket::class)->find($uriVariables['id']);

        if (!$ticketOriginal || $ticketOriginal->getIdentreprise() !== $identreprise) {
            throw new BadRequestHttpException('Ticket introuvable');
        }

        if ($ticketOriginal->getStatut() !== 'ACTIF') {
            throw new BadRequestHttpException('Seul un ticket actif peut faire l\'objet d\'un désistement');
        }

        if ($ticketOriginal->getVoyage()?->getDatefin() !== null) {
            throw new BadRequestHttpException('Impossible de reporter un ticket sur un voyage clôturé');
        }

        $nouveauVoyage = $data->nouveauVoyage;
        $nouveauSiege  = $data->nouveauSiege;
        $gare          = $data->gare;

        // Vérifier que le nouveau voyage est actif
        if ($nouveauVoyage->getDatefin() !== null) {
            throw new BadRequestHttpException('Le nouveau voyage est déjà clôturé');
        }

        // Vérifier que le nouveau siège est libre
        $siegeOccupe = $this->ticketRepository->findOneBy([
            'siege'     => $nouveauSiege,
            'voyage'    => $nouveauVoyage,
            'statut'    => 'ACTIF',
            'deletedAt' => null,
        ]);

        if ($siegeOccupe) {
            throw new BadRequestHttpException(
                sprintf('Le siège %d est déjà occupé sur ce voyage', $nouveauSiege->getNumero())
            );
        }

        // Vérifier que le siège appartient au car du nouveau voyage
        if ($nouveauSiege->getCar()->getId() !== $nouveauVoyage->getCar()?->getId()) {
            throw new BadRequestHttpException('Ce siège n\'appartient pas au véhicule du nouveau voyage');
        }

        // Calculer le prix selon le tarif du nouveau voyage
        $tarif = $this->tarifRepository->findOneBy([
            'trajet'       => $nouveauVoyage->getTrajet(),
            'identreprise' => $identreprise,
        ]);
        $nouveauPrix = $tarif ? (int) $tarif->getPrix() : $ticketOriginal->getPrix();

        // ── 1. Marquer l'ancien ticket comme DESISTE ──────────────────────
        $ticketOriginal->setStatut('DESISTE');
        $ticketOriginal->setUpdatedBy($user->getId());
        $this->em->persist($ticketOriginal);

        // Décrémenter placesoccupees de l'ancien voyage
        $ancienVoyage = $ticketOriginal->getVoyage();
        if ($ancienVoyage && $ancienVoyage->getPlacesOccupees() > 0) {
            $ancienVoyage->setPlacesOccupees($ancienVoyage->getPlacesOccupees() - 1);
            $this->em->persist($ancienVoyage);
        }

        // ── 2. Créer le nouveau ticket ────────────────────────────────────
        $count = $this->ticketRepository->count([
            'identreprise' => $identreprise,
            'deletedAt'    => null,
        ]) + 1;

        $nouveauTicket = new Ticket();
        $nouveauTicket
            ->setVoyage($nouveauVoyage)
            ->setSiege($nouveauSiege)
            ->setGare($gare)
            ->setNomclient($ticketOriginal->getNomclient())
            ->setContactclient($ticketOriginal->getContactclient())
            ->setPrix($nouveauPrix)
            ->setStatut('ACTIF')
            ->setIdentreprise($identreprise)
            ->setCreatedBy($user->getId())
            ->setCodeticket(
                $nouveauVoyage->getTrajet()->getCodetrajet()
                . '-T' . str_pad($count, 5, '0', STR_PAD_LEFT)
            )
            ->setTicketOrigine($ticketOriginal);

        // Incrémenter placesoccupees du nouveau voyage
        $nouveauVoyage->setPlacesOccupees($nouveauVoyage->getPlacesOccupees() + 1);
        $this->em->persist($nouveauVoyage);
        $this->em->persist($nouveauTicket);

        // Lier l'ancien ticket au nouveau
        $ticketOriginal->setTicketReport($nouveauTicket);
        $this->em->persist($ticketOriginal);

        $this->em->flush();

        return $nouveauTicket;
    }
}
```

---

### 5 — `TicketController` — action `desistement`

```php
#[Route('/{id}/desistement', name: 'desistement', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
#[IsGranted('TICKET_MODIFIER')]
public function desistement(int $id, Request $request): Response
{
    try {
        $ticket  = $this->api->item('/api/tickets/' . $id);
        $voyages = array_values(array_filter(
            $this->api->collection('/api/voyages'),
            fn($v) => empty($v['datefin']) && $v['id'] !== $ticket['voyage']['id']
        ));
        $gares = $this->api->collection('/api/gares');
    } catch (ApiException $e) {
        $response = $this->apiExceptionHandler->handle($e, null, 'ticket.show', ['id' => $id]);
        if ($response) return $response;
    }

    if ($request->isMethod('POST')) {
        $data = $request->request->all();

        try {
            $nouveauTicket = $this->api->post("/api/tickets/{$id}/desistement", [
                'nouveauVoyage' => '/api/voyages/' . $data['voyage'],
                'nouveauSiege'  => '/api/sieges/'  . $data['siege'],
                'gare'          => '/api/gares/'   . $data['gare'],
            ]);

            $this->addFlash('success', 'Désistement enregistré. Nouveau ticket : ' . $nouveauTicket['codeticket']);
            return $this->redirectToRoute('ticket.show', ['id' => $nouveauTicket['id']]);

        } catch (ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'ticket.desistement', ['id' => $id]);
            if ($response) return $response;
        }
    }

    return $this->render('ticket/desistement.html.twig', [
        'ticket'  => $ticket,
        'voyages' => $voyages,
        'gares'   => $gares,
    ]);
}
```

---

### 6 — `ticket/desistement.html.twig`

```twig
{% extends 'base.html.twig' %}
{% block title %}Désistement — Ticket {{ ticket.codeticket }}{% endblock %}

{% block body %}
<div class="max-w-[600px] mx-auto">

    <div class="mb-6">
        <h1 class="text-4xl font-light tracking-[-0.55px]">Désistement</h1>
        <p class="text-sm text-muted-foreground mt-1">
            Reporter le ticket <span class="font-mono font-semibold">{{ ticket.codeticket }}</span>
            sur un nouveau voyage
        </p>
    </div>

    {# Récap ticket original #}
    <div class="rounded-xl border bg-card shadow-sm p-5 mb-6">
        <p class="text-xs text-muted-foreground uppercase tracking-wide font-medium mb-3">
            Ticket original
        </p>
        <div class="grid grid-cols-2 gap-3 text-sm">
            <div>
                <p class="text-muted-foreground text-xs">Voyage</p>
                <p class="font-medium">{{ ticket.voyage.provenance }} → {{ ticket.voyage.destination }}</p>
            </div>
            <div>
                <p class="text-muted-foreground text-xs">Siège</p>
                <p class="font-medium">{{ ticket.siege.numero }}</p>
            </div>
            <div>
                <p class="text-muted-foreground text-xs">Passager</p>
                <p class="font-medium">{{ ticket.nomclient ?? '—' }}</p>
            </div>
            <div>
                <p class="text-muted-foreground text-xs">Prix payé</p>
                <p class="font-semibold text-emerald-600">
                    {{ ticket.prix|number_format(0, ',', ' ') }} FCFA
                </p>
            </div>
        </div>
    </div>

    {# Formulaire report #}
    <form method="POST" action="{{ path('ticket.desistement', { id: ticket.id }) }}">
        <div class="rounded-xl border bg-card shadow-sm p-5 space-y-4">
            <p class="text-xs text-muted-foreground uppercase tracking-wide font-medium">
                Nouveau départ
            </p>

            {# Nouveau voyage #}
            <div class="space-y-1.5">
                <label class="text-sm font-medium">Nouveau voyage</label>
                <select
                    name="voyage"
                    required
                    id="select-voyage"
                    class="input w-full"
                    data-remote-select="voyages"
                >
                    <option value="">-- Sélectionner un voyage --</option>
                    {% for v in voyages %}
                    <option value="{{ v.id }}">
                        {{ v.codevoyage }} — {{ v.provenance }} → {{ v.destination }}
                        ({{ v.placestotal - v.placesoccupees }} places restantes)
                    </option>
                    {% endfor %}
                </select>
            </div>

            {# Nouveau siège — chargé dynamiquement #}
            <div class="space-y-1.5" id="siege-wrapper" style="display:none">
                <label class="text-sm font-medium">Nouveau siège</label>
                <select name="siege" required id="select-siege" class="input w-full">
                    <option value="">-- Sélectionner un siège --</option>
                </select>
            </div>

            {# Gare #}
            <div class="space-y-1.5">
                <label class="text-sm font-medium">Gare d'embarquement</label>
                <select
                    name="gare"
                    required
                    class="input w-full"
                    data-remote-select="gares"
                >
                    <option value="">-- Sélectionner une gare --</option>
                    {% for g in gares %}
                    <option value="{{ g.id }}">{{ g.libelle }}</option>
                    {% endfor %}
                </select>
            </div>

            {# Différence de prix — affiché dynamiquement #}
            <div id="diff-prix" class="hidden rounded-lg px-4 py-3 text-sm"></div>
        </div>

        <div class="flex items-center justify-between mt-4">
            <a href="{{ path('ticket.show', { id: ticket.id }) }}" class="btn btn-outline btn-sm">
                ← Annuler
            </a>
            <button type="submit" class="btn btn-primary btn-sm">
                Confirmer le désistement
            </button>
        </div>
    </form>
</div>

<script>
(function () {
    const selectVoyage = document.getElementById('select-voyage')
    const selectSiege  = document.getElementById('select-siege')
    const siegeWrapper = document.getElementById('siege-wrapper')
    const diffPrix     = document.getElementById('diff-prix')
    const prixOriginal = {{ ticket.prix }}

    selectVoyage?.addEventListener('change', async (e) => {
        const voyageId = e.target.value
        if (!voyageId) {
            siegeWrapper.style.display = 'none'
            return
        }

        // Charger les sièges libres du nouveau voyage
        const res  = await fetch(`/ticket/sieges/${voyageId}`)
        const data = await res.json()

        selectSiege.innerHTML = '<option value="">-- Sélectionner un siège --</option>'
        data.sieges
            .filter(s => s.statut === 'LIBRE')
            .forEach(s => {
                const opt = document.createElement('option')
                opt.value       = s.id
                opt.textContent = `Siège ${s.numero} (${s.cote})`
                selectSiege.appendChild(opt)
            })

        siegeWrapper.style.display = 'block'

        // Afficher la différence de prix si tarif différent
        const voyageOpt = e.target.selectedOptions[0]
        // Optionnel : fetch /api/tarifs?trajet=... pour comparer
    })
})()
</script>
{% endblock %}
```

---

### 7 — Afficher l'historique sur `ticket/show.html.twig`

```twig
{# Si ce ticket est un report #}
{% if ticket.ticketOrigine %}
<div class="rounded-xl border border-amber-200 bg-amber-50/50 p-4 text-sm">
    <p class="font-medium text-amber-700 mb-1">Ce ticket est un report</p>
    <p class="text-amber-600">
        Ticket original :
        <a href="{{ path('ticket.show', { id: ticket.ticketOrigine.id }) }}"
           class="font-mono font-semibold hover:underline">
            {{ ticket.ticketOrigine.codeticket }}
        </a>
        — {{ ticket.ticketOrigine.voyage.provenance }} → {{ ticket.ticketOrigine.voyage.destination }}
    </p>
</div>
{% endif %}

{# Si ce ticket a été reporté #}
{% if ticket.ticketReport %}
<div class="rounded-xl border border-zinc-200 bg-zinc-50/50 p-4 text-sm">
    <p class="font-medium text-zinc-600 mb-1">Ce ticket a fait l'objet d'un désistement</p>
    <p class="text-zinc-500">
        Nouveau ticket :
        <a href="{{ path('ticket.show', { id: ticket.ticketReport.id }) }}"
           class="font-mono font-semibold hover:underline">
            {{ ticket.ticketReport.codeticket }}
        </a>
        — {{ ticket.ticketReport.voyage.provenance }} → {{ ticket.ticketReport.voyage.destination }}
    </p>
</div>
{% endif %}

{# Bouton désistement #}
{% if is_granted('TICKET_MODIFIER') and ticket.statut == 'ACTIF' and not ticket.voyage.datefin and not ticket.ticketReport %}
<a href="{{ path('ticket.desistement', { id: ticket.id }) }}" class="btn btn-outline btn-sm gap-1.5">
    Report de voyage
</a>
{% endif %}
```

---

### Récap

Le désistement crée une chaîne traçable : `ticketOriginal (DESISTE) → ticketNouveau (ACTIF)`. Les stats comptent le ticket original comme vendu (il reste en base avec `statut = DESISTE`) et comptent aussi le nouveau ticket. Le siège de l'ancien voyage est libéré immédiatement. Le prix est recalculé selon le tarif du nouveau trajet.








Le flux le plus adapté est **l'autocomplete sur le codeticket ou nomclient** pour ces raisons :

- L'agent au chargement a le ticket physique du client devant lui → il peut scanner/saisir le code directement
- Chercher par voyage puis ticket ajoute une étape inutile
- L'autocomplete via `/autocomplete/tickets?q=xxx` cherche sur `codeticket` ET `nomclient` simultanément — flexible

Le flux devient :

```
Agent saisit les 3 premiers caractères du codeticket ou nom du client
        ↓
Autocomplete retourne les tickets correspondants du voyage en cours
        ↓
Agent sélectionne → nomclient et contactclient pré-remplis automatiquement
        ↓
Agent saisit nature, type, valeur → tarif calculé automatiquement
```

---

Voici tous les changements :

---

## 1. Entité `Bagage` — modifications

```php
// poids nullable
#[ORM\Column(type: 'decimal', precision: 8, scale: 2, nullable: true)]
#[Groups(['read:Bagage', 'write:BagageInput'])]
private ?string $poids = null;

// valeur déclarée — base du calcul tarifaire
#[ORM\Column]
#[Groups(['read:Bagage', 'write:BagageInput'])]
private ?int $valeur = null;

// codeticket lié
#[ORM\Column(length: 255, nullable: true)]
#[Groups(['read:Bagage', 'write:BagageInput'])]
private ?string $codeticket = null;
```

---

## 2. `Tarifbagage` — renommer les champs poids → valeur

```php
// poidsmin → valeurmin
#[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
#[Groups(['read:Tarifbagage', 'read:Bagage', 'write:Tarifbagage', 'write:Tarifbagage:update'])]
private ?string $valeurmin = null;

// poidsmax → valeurmax
#[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
#[Groups(['read:Tarifbagage', 'read:Bagage', 'write:Tarifbagage', 'write:Tarifbagage:update'])]
private ?string $valeurmax = null;
```

---

## 3. `TarifbagageRepository` — méthode basée sur la valeur

```php
public function findTarifForValeur(int $valeur, int $identreprise): ?Tarifbagage
{
    return $this->createQueryBuilder('t')
        ->where('t.identreprise = :identreprise')
        ->andWhere('t.deletedAt IS NULL')
        ->andWhere('t.valeurmin <= :valeur')
        ->andWhere('t.valeurmax IS NULL OR t.valeurmax >= :valeur')
        ->setParameter('identreprise', $identreprise)
        ->setParameter('valeur', $valeur)
        ->orderBy('t.valeurmin', 'ASC')
        ->setMaxResults(1)
        ->getQuery()
        ->getOneOrNullResult();
}
```

Les méthodes `findTrancheIllimitee` et `findChevauchement` utilisent maintenant `valeurmin/valeurmax` — renommer les paramètres en conséquence.

---

## 4. `BagageInput` — modifications

```php
#[Assert\NotNull]
#[Groups(['write:BagageInput'])]
public int $voyage;

#[Assert\NotBlank]
#[Groups(['write:BagageInput'])]
public string $nomclient;

#[Assert\NotBlank]
#[Groups(['write:BagageInput'])]
public string $contactclient;

#[Assert\NotBlank]
#[Groups(['write:BagageInput'])]
public string $nature;

#[Assert\NotBlank]
#[Assert\Choice(choices: ['LEGER', 'LOURD', 'VOLUMINEUX', 'FRAGILE'])]
#[Groups(['write:BagageInput'])]
public string $type;

// poids nullable
#[Groups(['write:BagageInput'])]
public ?float $poids = null;

// valeur déclarée — obligatoire pour le calcul tarifaire
#[Assert\NotNull]
#[Assert\Positive]
#[Groups(['write:BagageInput'])]
public int $valeur;

// montant forcé optionnel
#[Assert\PositiveOrZero]
#[Groups(['write:BagageInput'])]
public ?int $montant = null;

// codeticket lié optionnel
#[Groups(['write:BagageInput'])]
public ?string $codeticket = null;
```

---

## 5. `BagageProcessor` — calcul basé sur la valeur

```php
private function resoudreMontant(int $valeur, ?int $montantFourni, int $identreprise): array
{
    $tarifbagage = $this->tarifbagageRepository->findTarifForValeur($valeur, $identreprise);

    if ($tarifbagage !== null) {
        $montantCalcule = $tarifbagage->getMontant();
        if ($montantFourni !== null && $montantFourni !== $montantCalcule) {
            return [$montantFourni, $tarifbagage, true];
        }
        return [$montantCalcule, $tarifbagage, false];
    }

    if ($montantFourni === null) {
        throw new BadRequestHttpException(
            'Aucun tarif trouvé pour une valeur de ' . $valeur . ' FCFA. Veuillez saisir le montant manuellement.'
        );
    }

    return [$montantFourni, null, true];
}
```

Dans `handlePost` et `handlePatch`, remplacer `$data->poids` par `$data->valeur` :

```php
[$montant, $tarifbagage, $montantforce] = $this->resoudreMontant(
    $data->valeur,      // ← valeur au lieu de poids
    $data->montant,
    $identreprise
);

$bagage
    ->setPoids($data->poids !== null ? (string) $data->poids : null)  // nullable
    ->setValeur($data->valeur)
    ->setCodeticket($data->codeticket)
    // ...
```

---

## 6. `AutocompleteController` — endpoint tickets

```php
#[Route('/tickets', name: 'tickets', methods: ['GET'])]
public function tickets(Request $request): JsonResponse
{
    return $this->search('/api/tickets', $request, [
        'q'    => 'codeticket',  // SearchFilter partial sur codeticket
        'text' => fn($item) => $item['codeticket']
            . ' — ' . ($item['nomclient'] ?? 'Client inconnu')
            . ' (' . ($item['voyage']['codevoyage'] ?? '') . ')',
        'extra_filters' => [
            'voyage.datefin' => null, // uniquement voyages non clôturés si filtre disponible
        ]
    ]);
}
```

---

## 7. Frontend — `bagage/_form.html.twig`

Remplacer le champ voyage par le select ticket avec autocomplete + pré-remplissage automatique :

```twig
<div class="card p-6">
    <h2 class="text-lg font-semibold mb-4">Client</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

        {# Autocomplete ticket #}
        <div class="form-group sm:col-span-2">
            <label class="form-label" for="codeticket">
                Ticket du client (optionnel)
            </label>
            <input
                type="text"
                id="codeticket-search"
                class="input"
                placeholder="Saisir le code ticket ou nom du client..."
                data-controller="api-select"
                data-api-select-url-value="{{ path('autocomplete.tickets') }}"
                data-api-select-placeholder-value="Rechercher un ticket..."
                autocomplete="off"
            >
            {# Champ caché pour stocker le codeticket sélectionné #}
            <input type="hidden" name="codeticket" id="codeticket"
                   value="{{ bagage.codeticket ?? '' }}">
        </div>

        {# Voyage — rempli automatiquement depuis le ticket ou saisi manuellement #}
        <div class="form-group">
            <label class="form-label" for="voyage">
                Voyage <span class="text-red-500">*</span>
            </label>
            <select id="voyage" name="voyage" class="input" required
                    data-controller="api-select"
                    data-api-select-url-value="{{ path('autocomplete.voyages') }}"
                    data-api-select-placeholder-value="Rechercher un voyage...">
                {% if bagage is not null and bagage.voyage is defined %}
                    <option value="{{ bagage.voyage.id }}" selected>
                        {{ bagage.voyage.codevoyage }}
                    </option>
                {% endif %}
            </select>
        </div>

        <div class="form-group">
            <label class="form-label" for="nomclient">
                Nom du client <span class="text-red-500">*</span>
            </label>
            <input type="text" id="nomclient" name="nomclient" class="input"
                   value="{{ bagage.nomclient ?? '' }}" required>
        </div>

        <div class="form-group">
            <label class="form-label" for="contactclient">
                Contact <span class="text-red-500">*</span>
            </label>
            <input type="text" id="contactclient" name="contactclient" class="input"
                   value="{{ bagage.contactclient ?? '' }}" required>
        </div>
    </div>
</div>
```

---

## 8. Stimulus controller — pré-remplissage depuis ticket sélectionné

```javascript
// assets/controllers/ticket_bagage_controller.js
import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static targets = ['codeticket', 'voyage', 'nomclient', 'contactclient']

    selectTicket(event) {
        const ticket = event.detail  // {id, codeticket, nomclient, contactclient, voyage}
        if (!ticket) return

        // Pré-remplir les champs
        this.codeticketTarget.value    = ticket.codeticket
        this.nomclientTarget.value     = ticket.nomclient ?? ''
        this.contactclientTarget.value = ticket.contactclient ?? ''

        // Pré-remplir le voyage si disponible
        if (ticket.voyage) {
            const option = new Option(
                ticket.voyage.codevoyage,
                ticket.voyage.id,
                true,
                true
            )
            this.voyageTarget.tomselect?.addOption({ value: ticket.voyage.id, text: ticket.voyage.codevoyage })
            this.voyageTarget.tomselect?.setValue(ticket.voyage.id)
        }
    }
}
```

Et dans `AutocompleteController` — enrichir la réponse tickets avec les données du client :

```php
#[Route('/tickets', name: 'tickets', methods: ['GET'])]
public function tickets(Request $request): JsonResponse
{
    $query = trim($request->query->get('query', ''));

    $params = [
        'codeticket' => $query,
        'itemsPerPage' => 10,
    ];

    try {
        $items = $this->api->collection('/api/tickets', $params);
    } catch (ApiException) {
        return $this->json(['results' => []]);
    }

    $results = array_map(fn($item) => [
        'value'         => $item['codeticket'],
        'text'          => $item['codeticket'] . ' — ' . ($item['nomclient'] ?? '?'),
        // Données supplémentaires pour le pré-remplissage
        'nomclient'     => $item['nomclient'] ?? '',
        'contactclient' => $item['contactclient'] ?? '',
        'voyage'        => $item['voyage'] ?? null,
    ], $items);

    return $this->json(['results' => $results]);
}
```

---

## 9. Migration

```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

N'oublie pas de renommer les champs dans `TarifbagageFormType` (`poidsmin/poidsmax` → `valeurmin/valeurmax`) et dans `TarifbagageTable.tsx`. Quelle est la suite ?









Tu as raison — si un colis est perdu la compagnie doit rembourser sa valeur, donc **le montant ne change pas** (c'est la dette de la compagnie). Le recalcul n'a pas de sens ici.

On garde donc :
- Montant inchangé
- Statut `PERDU` sur le `Detailcourrier`
- Si tous les colis sont `PERDU` → `Courrier` passe automatiquement à `PERDU`

---

## Backend

### Ajout `statut` dans `Detailcourrier`

```php
public const STATUT_NORMAL = 'NORMAL';
public const STATUT_PERDU  = 'PERDU';

#[ORM\Column(length: 50)]
#[Groups(['read:Detailcourrier', 'read:Courrier'])]
private string $statut = self::STATUT_NORMAL;

public function getStatut(): string { return $this->statut; }
public function setStatut(string $statut): static { $this->statut = $statut; return $this; }
```

---

### Endpoint dans `Detailcourrier`

```php
new Patch(
    security: "is_granted('MODIFIER', object)",
    uriTemplate: '/detailcourriers/{id}/perdu',
    requirements: ['id' => '\d+'],
    input: false,
    processor: PerduDetailcourrierProcessor::class,
    openapi: new Operation(
        summary: 'Déclarer un colis comme perdu',
        description: 'Marque le colis comme perdu et met à jour le statut du courrier si nécessaire',
        security: [['bearerAuth' => []]]
    )
),
```

---

### `PerduDetailcourrierProcessor`

```php
<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Domain\Enum\CourrierStatus;
use App\Entity\Detailcourrier;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class PerduDetailcourrierProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $processor,
        private Security $security,
        private EntityManagerInterface $em
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var Detailcourrier $data */
        /** @var User $user */
        $user = $this->security->getUser();

        // Un colis déjà perdu ne peut pas être re-déclaré perdu
        if ($data->getStatut() === Detailcourrier::STATUT_PERDU) {
            throw new BadRequestHttpException('Ce colis est déjà déclaré perdu');
        }

        // Le courrier doit être en transit ou réceptionné pour déclarer un colis perdu
        $courrier = $data->getCourrier();
        if (!in_array($courrier->getStatut(), [
            CourrierStatus::STATUT_EN_TRANSIT->value,
            CourrierStatus::STATUT_RECEPTIONNE->value,
        ])) {
            throw new BadRequestHttpException(
                'Un colis ne peut être déclaré perdu que si le courrier est en transit ou réceptionné'
            );
        }

        // Marquer le colis comme perdu
        $data->setStatut(Detailcourrier::STATUT_PERDU);

        // Vérifier si tous les colis sont perdus
        $tousLesColissPerdus = true;
        foreach ($courrier->getDetailcourriers() as $detail) {
            // Exclure le colis actuel (déjà mis à jour en mémoire)
            if ($detail->getId() === $data->getId()) continue;

            if ($detail->getStatut() !== Detailcourrier::STATUT_PERDU) {
                $tousLesColissPerdus = false;
                break;
            }
        }

        if ($tousLesColissPerdus) {
            $courrier->setStatut(CourrierStatus::STATUT_PERDU->value);
            $this->em->persist($courrier);
        }

        return $this->processor->process($data, $operation, $uriVariables, $context);
    }
}
```

---

### Migration

```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

---

## Frontend

### Route dans `CourrierController`

```php
#[Route('/colis/{id}/perdu', name: 'colis.perdu', methods: ['POST'], requirements: ['id' => Requirement::DIGITS])]
#[IsGranted('COURRIER_MODIFIER')]
public function colisPerdu(int $id, Request $request): Response
{
    $courrierId = $request->request->get('courrier_id');

    try {
        $this->api->patch('/api/detailcourriers/' . $id . '/perdu');
        $this->addFlash('success', 'Le colis a été déclaré perdu');
    } catch (ApiException $e) {
        $response = $this->apiExceptionHandler->handle($e, null, 'courrier.show', ['id' => $courrierId]);
        if ($response) return $response;
    }

    return $this->redirectToRoute('courrier.show', ['id' => $courrierId]);
}
```

---

### Dans `courrier/show.html.twig` — table des colis

```twig
{% set typeMap = {
    NORMAL:     ['Normal',     'bg-gray-100 text-gray-700'],
    FRAGILE:    ['Fragile',    'bg-yellow-50 text-yellow-700'],
    VOLUMINEUX: ['Volumineux', 'bg-purple-50 text-purple-700']
} %}

{% for detail in courrier.detailcourriers %}
{% set tc = typeMap[detail.type] ?? [detail.type, 'bg-gray-100 text-gray-700'] %}
<tr class="hover:bg-muted/20 transition-colors {{ detail.statut == 'PERDU' ? 'opacity-60' : '' }}">
    <td class="px-4 py-3">{{ detail.nature }}</td>
    <td class="px-4 py-3 font-medium">{{ detail.designation }}</td>
    <td class="px-4 py-3 text-muted-foreground">{{ detail.emballage ?? '—' }}</td>
    <td class="px-4 py-3">
        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ tc[1] }}">
            {{ tc[0] }}
        </span>
    </td>
    <td class="px-4 py-3 text-muted-foreground">{{ detail.poids ? detail.poids ~ ' kg' : '—' }}</td>
    <td class="px-4 py-3 tabular-nums">{{ detail.valeur|number_format(0, ',', ' ') }} FCFA</td>
    <td class="px-4 py-3 text-right tabular-nums font-semibold">
        {{ detail.montant|number_format(0, ',', ' ') }} FCFA
    </td>
    <td class="px-4 py-3">
        {% if detail.statut == 'PERDU' %}
            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-red-100 text-red-800">
                Perdu
            </span>
        {% else %}
            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-green-100 text-green-800">
                Normal
            </span>
        {% endif %}
    </td>
    <td class="px-4 py-3">
        {% if is_granted('COURRIER_MODIFIER')
            and detail.statut == 'NORMAL'
            and courrier.statut in ['EN_TRANSIT', 'RECEPTIONNE'] %}
        <form method="POST"
              action="{{ path('courrier.colis.perdu', { id: detail.id }) }}"
              class="inline"
              onsubmit="return confirm('Déclarer ce colis comme perdu ?')">
            <input type="hidden" name="courrier_id" value="{{ courrier.id }}">
            <button type="submit" class="btn btn-danger btn-sm">
                Déclarer perdu
            </button>
        </form>
        {% endif %}
    </td>
</tr>
{% endfor %}
```

Quelle est la suite ?





## Backend

### `DeleteAccountInput`

```php
<?php

namespace App\Entity\Dto;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class DeleteAccountInput
{
    #[Assert\NotBlank(message: 'Le mot de passe est requis')]
    #[Groups(['write:DeleteAccount'])]
    public string $password;
}
```

---

### Endpoint dans l'entité `User`

```php
new Post(
    security: "is_granted('ROLE_USER')",
    uriTemplate: '/me/supprimer',
    input: DeleteAccountInput::class,
    processor: DeleteAccountProcessor::class,
    denormalizationContext: ['groups' => ['write:DeleteAccount']],
    openapi: new Operation(
        summary: 'Suppression du compte utilisateur',
        description: 'Permet à un utilisateur de supprimer définitivement son compte',
        security: [['bearerAuth' => []]]
    )
),
```

---

### `DeleteAccountProcessor`

```php
<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Dto\DeleteAccountInput;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class DeleteAccountProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var DeleteAccountInput $data */
        /** @var User $user */
        $user = $this->security->getUser();

        // Empêcher l'admin de supprimer son compte
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            throw new BadRequestHttpException(
                'L\'administrateur ne peut pas supprimer son compte. Contactez le support.'
            );
        }

        // Vérification du mot de passe
        if (!$this->hasher->isPasswordValid($user, $data->password)) {
            throw new BadRequestHttpException('Mot de passe incorrect');
        }

        $this->em->remove($user);
        $this->em->flush();

        return null;
    }
}
```

---

## Frontend

### `DeleteAccountType`

```php
<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class DeleteAccountType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('password', PasswordType::class, [
            'label'      => 'Mot de passe',
            'attr'       => [
                'placeholder' => '••••••••',
                'class'       => 'input',
                'autofocus'   => true,
            ],
            'label_attr' => ['class' => 'form-label'],
            'constraints' => [
                new Assert\NotBlank(message: 'Le mot de passe est requis'),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => null]);
    }
}
```

---

### Route dans `ProfilController`

```php
#[Route('/profil/supprimer-compte', name: 'profil.delete_account', methods: ['GET', 'POST'])]
#[IsGranted('ROLE_USER')]
public function deleteAccount(Request $request): Response
{
    // Bloquer l'admin
    if ($this->isGranted('ROLE_ADMIN')) {
        $this->addFlash('error', 'L\'administrateur ne peut pas supprimer son compte.');
        return $this->redirectToRoute('profil.index');
    }

    $form = $this->createForm(DeleteAccountType::class);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        try {
            $this->api->post('/api/me/supprimer', [
                'password' => $form->get('password')->getData(),
            ]);

            // Déconnexion après suppression
            $this->container->get('security.token_storage')->setToken(null);
            $request->getSession()->invalidate();

            $this->addFlash('success', 'Votre compte a été supprimé avec succès.');
            return $this->redirectToRoute('app_login');

        } catch (ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, $form, 'profil.delete_account');
            if ($response) return $response;
        }
    }

    return $this->render('profil/delete_account.html.twig', [
        'form' => $form,
    ]);
}
```

---

### `profil/delete_account.html.twig`

```twig
{% extends 'base.html.twig' %}
{% block title %}Supprimer mon compte{% endblock %}
{% block body %}

<div class="flex items-center gap-3 mb-6">
    <a href="{{ path('profil.index') }}" class="btn-icon">
        <svg class="h-5 w-5" aria-hidden="true"><use href="{{ asset('sprite.svg') }}#chevron-left"></use></svg>
    </a>
    <h1 class="text-3xl font-light tracking-[-0.55px] leading-[1.2]">Supprimer mon compte</h1>
</div>

<div class="max-w-lg space-y-4">
    <div class="rounded-xl border border-red-200 bg-red-50 dark:bg-red-950/30 dark:border-red-800 p-4">
        <p class="font-semibold text-red-700 dark:text-red-400 mb-1">Attention — action irréversible</p>
        <p class="text-sm text-red-600 dark:text-red-500">
            La suppression de votre compte est définitive. Toutes vos données personnelles seront effacées
            et vous ne pourrez plus vous connecter.
        </p>
    </div>

    {{ form_start(form, { attr: { class: 'card p-6 space-y-4' } }) }}

        <p class="text-sm text-muted-foreground">
            Confirmez votre mot de passe pour supprimer définitivement votre compte.
        </p>

        <div class="form-group">
            {{ form_label(form.password) }}
            {{ form_widget(form.password) }}
            {{ form_errors(form.password) }}
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit"
                    class="btn btn-danger"
                    onclick="return confirm('Êtes-vous sûr de vouloir supprimer définitivement votre compte ? Cette action est irréversible.')">
                Supprimer mon compte
            </button>
            <a href="{{ path('profil.index') }}" class="btn btn-secondary">Annuler</a>
        </div>

    {{ form_end(form) }}
</div>

{% endblock %}
```

---

### Lien dans la page profil

```twig
{% if not is_granted('ROLE_ADMIN') %}
<div class="card p-5 border-red-200 dark:border-red-800">
    <h2 class="text-lg font-semibold text-red-700 dark:text-red-400 mb-2">Zone dangereuse</h2>
    <p class="text-sm text-muted-foreground mb-4">
        La suppression de votre compte est définitive et irréversible.
    </p>
    <a href="{{ path('profil.delete_account') }}" class="btn btn-danger btn-sm">
        Supprimer mon compte
    </a>
</div>
{% endif %}
```

Quelle est la suite ?







Pas de problème. Voici une modal en vanilla JS cohérente avec ton style Tailwind existant, sans dépendance externe.

---

## `profil/delete_account.html.twig` — suppression de la page dédiée

La page dédiée n'est plus nécessaire — tout se passe dans la modal depuis la page profil.

---

## Dans `profil/index.html.twig` — zone dangereuse + modal

```twig
{% if not is_granted('ROLE_ADMIN') %}

{# -- Bouton déclencheur -- #}
<div class="card p-5 border border-red-200 dark:border-red-800">
    <h2 class="text-lg font-semibold text-red-700 dark:text-red-400 mb-2">Zone dangereuse</h2>
    <p class="text-sm text-muted-foreground mb-4">
        La suppression de votre compte est définitive et irréversible.
    </p>
    <button type="button" id="btn-open-delete-modal" class="btn btn-danger btn-sm">
        Supprimer mon compte
    </button>
</div>

{# -- Modal -- #}
<div id="delete-account-modal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-card rounded-xl shadow-xl w-full max-w-md border">

        {# En-tête #}
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <h2 class="text-lg font-semibold">Supprimer mon compte</h2>
            <button type="button" id="btn-close-delete-modal"
                    class="text-muted-foreground hover:text-foreground transition-colors">
                <svg class="h-5 w-5" aria-hidden="true"><use href="{{ asset('sprite.svg') }}#close"></use></svg>
            </button>
        </div>

        {# Corps #}
        <div class="px-6 py-4 space-y-4">
            <div class="rounded-lg border border-red-200 bg-red-50 dark:bg-red-950/30 dark:border-red-800 p-3">
                <p class="text-sm font-semibold text-red-700 dark:text-red-400 mb-1">
                    Action irréversible
                </p>
                <p class="text-xs text-red-600 dark:text-red-500">
                    Toutes vos données personnelles seront effacées définitivement.
                    Vous ne pourrez plus vous connecter.
                </p>
            </div>

            <form method="POST"
                  action="{{ path('profil.delete_account') }}"
                  id="delete-account-form"
                  class="space-y-4">

                <div class="form-group">
                    <label class="form-label" for="delete-password">
                        Confirmez votre mot de passe <span class="text-red-500">*</span>
                    </label>
                    <input type="password"
                           id="delete-password"
                           name="password"
                           class="input"
                           placeholder="••••••••"
                           required
                           autofocus>
                    <p class="text-xs text-muted-foreground mt-1">
                        Saisissez votre mot de passe pour confirmer la suppression.
                    </p>
                </div>

                {% for message in app.flashes('delete_error') %}
                    <div class="rounded-lg border border-destructive/30 bg-destructive/10 p-3 text-sm text-destructive">
                        {{ message }}
                    </div>
                {% endfor %}

            </form>
        </div>

        {# Pied #}
        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t">
            <button type="button" id="btn-cancel-delete" class="btn btn-secondary">
                Annuler
            </button>
            <button type="submit"
                    form="delete-account-form"
                    class="btn btn-danger">
                Supprimer définitivement
            </button>
        </div>

    </div>
</div>

<script>
(function () {
    const modal     = document.getElementById('delete-account-modal')
    const btnOpen   = document.getElementById('btn-open-delete-modal')
    const btnClose  = document.getElementById('btn-close-delete-modal')
    const btnCancel = document.getElementById('btn-cancel-delete')
    const form      = document.getElementById('delete-account-form')

    function openModal() {
        modal.classList.remove('hidden')
        modal.classList.add('flex')
        document.getElementById('delete-password').focus()
    }

    function closeModal() {
        modal.classList.add('hidden')
        modal.classList.remove('flex')
        document.getElementById('delete-password').value = ''
    }

    btnOpen.addEventListener('click', openModal)
    btnClose.addEventListener('click', closeModal)
    btnCancel.addEventListener('click', closeModal)

    // Fermer en cliquant sur l'overlay
    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeModal()
    })

    // Fermer avec Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal()
    })

    // Confirmation avant soumission
    form.addEventListener('submit', function (e) {
        const password = document.getElementById('delete-password').value
        if (!password) {
            e.preventDefault()
            return
        }
        if (!confirm('Êtes-vous sûr de vouloir supprimer définitivement votre compte ?')) {
            e.preventDefault()
        }
    })
})()
</script>

{% endif %}
```

---

## `ProfilController::deleteAccount` — adapté pour la modal

La route reste la même mais redirige vers `profil.index` avec un flash dédié en cas d'erreur :

```php
#[Route('/profil/supprimer-compte', name: 'profil.delete_account', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
public function deleteAccount(Request $request): Response
{
    if ($this->isGranted('ROLE_ADMIN')) {
        $this->addFlash('error', 'L\'administrateur ne peut pas supprimer son compte.');
        return $this->redirectToRoute('profil.index');
    }

    $password = $request->request->get('password', '');

    if (empty($password)) {
        $this->addFlash('delete_error', 'Le mot de passe est requis.');
        return $this->redirectToRoute('profil.index');
    }

    try {
        $this->api->post('/api/me/supprimer', [
            'password' => $password,
        ]);

        $this->container->get('security.token_storage')->setToken(null);
        $request->getSession()->invalidate();

        $this->addFlash('success', 'Votre compte a été supprimé avec succès.');
        return $this->redirectToRoute('app_login');

    } catch (ApiException $e) {
        // On renvoie l'erreur dans un flash dédié pour la modal
        $this->addFlash('delete_error', $e->getMessage());
        return $this->redirectToRoute('profil.index');
    }
}
```

Le flash `delete_error` est affiché dans la modal — mais après une redirection la modal est fermée. Pour rouvrir la modal automatiquement si une erreur s'est produite, on ajoute ce JS :

```twig
{% if app.flashes('delete_error') is not empty %}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('btn-open-delete-modal').click()
    })
</script>
{% endif %}
```

Mais comme `app.flashes` consomme les messages, il faut l'appeler **une seule fois** — le `{% for message in app.flashes('delete_error') %}` dans la modal les affichera et cette vérification JS doit se faire différemment :

```twig
{% set deleteErrors = app.flashes('delete_error') %}

{# Dans le JS #}
{% if deleteErrors is not empty %}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('btn-open-delete-modal').click()
    })
</script>
{% endif %}

{# Dans la modal, utiliser la variable deleteErrors #}
{% for message in deleteErrors %}
    <div class="rounded-lg border border-destructive/30 bg-destructive/10 p-3 text-sm text-destructive">
        {{ message }}
    </div>
{% endfor %}
```

Quelle est la suite ?








Parfait. On implémente les deux — fichier `.lock` pour le backend API, base de données pour le frontend (piloté par le super admin via interface).

---

## Approche 1 — Fichier `.lock` (Backend API)

### `MaintenanceSubscriber` — Backend

```php
<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class MaintenanceSubscriber implements EventSubscriberInterface
{
    private string $lockFile;

    public function __construct(string $projectDir)
    {
        $this->lockFile = $projectDir . '/maintenance.lock';
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 10]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) return;
        if (!file_exists($this->lockFile)) return;

        $request = $event->getRequest();

        // Super admin exempt
        $token = $request->headers->get('Authorization', '');
        // On laisse passer — la vérification ROLE_SUPER_ADMIN
        // est gérée dans le JWTEventSubscriber
        // Ici on bloque tout sauf si le header X-Maintenance-Bypass est présent
        if ($request->headers->get('X-Maintenance-Bypass') === $_ENV['MAINTENANCE_BYPASS_TOKEN'] ?? '') {
            return;
        }

        $message = file_get_contents($this->lockFile) ?: 'Maintenance en cours. Veuillez réessayer plus tard.';

        $event->setResponse(new JsonResponse([
            'detail'  => $message,
            'status'  => 503,
            'type'    => '/errors/503',
            'title'   => 'Service indisponible',
        ], 503));
    }
}
```

Dans `services.yaml` :

```yaml
App\EventSubscriber\MaintenanceSubscriber:
    arguments:
        $projectDir: '%kernel.project_dir%'
```

Dans `.env` :

```env
MAINTENANCE_BYPASS_TOKEN=un-token-secret-super-admin
```

---

## Approche 2 — Base de données (Frontend)

### Entité `Configuration`

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\ConfigurationRepository;

#[ORM\Entity(repositoryClass: ConfigurationRepository::class)]
class Configuration
{
    #[ORM\Id]
    #[ORM\Column(length: 100)]
    private string $cle;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $valeur = null;

    public function getCle(): string { return $this->cle; }
    public function setCle(string $cle): static { $this->cle = $cle; return $this; }
    public function getValeur(): ?string { return $this->valeur; }
    public function setValeur(?string $valeur): static { $this->valeur = $valeur; return $this; }
}
```

### `ConfigurationRepository`

```php
<?php

namespace App\Repository;

use App\Entity\Configuration;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ConfigurationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Configuration::class);
    }

    public function get(string $cle, mixed $default = null): mixed
    {
        $config = $this->find($cle);
        return $config ? $config->getValeur() : $default;
    }

    public function set(string $cle, mixed $valeur): void
    {
        $config = $this->find($cle) ?? (new Configuration())->setCle($cle);
        $config->setValeur($valeur);
        $this->getEntityManager()->persist($config);
        $this->getEntityManager()->flush();
    }
}
```

### Migration

```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

### `MaintenanceFrontendSubscriber` — Frontend Symfony

```php
<?php

namespace App\EventSubscriber;

use App\Repository\ConfigurationRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

class MaintenanceFrontendSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ConfigurationRepository $configurationRepository,
        private Security $security,
        private RouterInterface $router
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 8]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) return;

        $request    = $event->getRequest();
        $pathInfo   = $request->getPathInfo();

        // Ne pas bloquer la page maintenance elle-même
        if ($pathInfo === '/maintenance') return;

        // Ne pas bloquer les assets
        if (str_starts_with($pathInfo, '/_')) return;

        $maintenance = $this->configurationRepository->get('maintenance_active', '0');
        if ($maintenance !== '1') return;

        // Super admin exempt
        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) return;

        $event->setResponse(
            new RedirectResponse($this->router->generate('maintenance'))
        );
    }
}
```

---

## Endpoint API Platform pour le super admin

On ajoute une entité non persistée `Maintenance` :

```php
<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Operation;
use App\State\MaintenanceProvider;
use App\State\MaintenanceToggleProcessor;

#[ApiResource(
    security: "is_granted('ROLE_SUPER_ADMIN')",
    operations: [
        new Get(
            uriTemplate: '/maintenance/status',
            provider: MaintenanceProvider::class,
            openapi: new Operation(
                summary: 'Statut du mode maintenance',
                security: [['bearerAuth' => []]]
            )
        ),
        new Post(
            uriTemplate: '/maintenance/toggle',
            provider: CorbeilleEmptyProvider::class,
            processor: MaintenanceToggleProcessor::class,
            openapi: new Operation(
                summary: 'Activer/désactiver le mode maintenance',
                security: [['bearerAuth' => []]]
            )
        ),
    ]
)]
class Maintenance {}
```

### `MaintenanceProvider`

```php
<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\ConfigurationRepository;

class MaintenanceProvider implements ProviderInterface
{
    public function __construct(
        private ConfigurationRepository $configurationRepository
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $active  = $this->configurationRepository->get('maintenance_active', '0') === '1';
        $message = $this->configurationRepository->get('maintenance_message', 'Maintenance en cours.');

        return [
            'active'  => $active,
            'message' => $message,
        ];
    }
}
```

### `MaintenanceToggleProcessor`

```php
<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Repository\ConfigurationRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class MaintenanceToggleProcessor implements ProcessorInterface
{
    public function __construct(
        private ConfigurationRepository $configurationRepository,
        private RequestStack $requestStack
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        $request = $this->requestStack->getCurrentRequest();
        $body    = json_decode($request->getContent(), true);

        $current = $this->configurationRepository->get('maintenance_active', '0');
        $nouveau = $current === '1' ? '0' : '1';

        $this->configurationRepository->set('maintenance_active', $nouveau);

        // Message optionnel
        if (!empty($body['message'])) {
            $this->configurationRepository->set('maintenance_message', $body['message']);
        }

        return [
            'active'  => $nouveau === '1',
            'message' => $this->configurationRepository->get('maintenance_message', 'Maintenance en cours.'),
        ];
    }
}
```

---

## Page 503 — `maintenance.html.twig`

```twig
{% extends 'app-base.html.twig' %}
{% block title %}Maintenance{% endblock %}
{% block body %}

<div class="flex flex-col items-center justify-center text-center py-16 space-y-6">
    <div class="rounded-full bg-orange-100 dark:bg-orange-950/30 p-6">
        <svg class="h-16 w-16 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z" />
        </svg>
    </div>

    <div class="space-y-2">
        <h1 class="text-3xl font-semibold tracking-tight">Maintenance en cours</h1>
        <p class="text-muted-foreground max-w-md">
            Notre application est temporairement indisponible pour des opérations de maintenance.
            Veuillez réessayer dans quelques instants.
        </p>
    </div>

    <div class="rounded-lg border bg-muted/30 px-6 py-3 text-sm text-muted-foreground">
        Nous faisons de notre mieux pour revenir rapidement.
    </div>
</div>

{% endblock %}
```

### Route dans `SecurityController` ou un controller dédié

```php
#[Route('/maintenance', name: 'maintenance')]
public function maintenance(): Response
{
    return $this->render('security/maintenance.html.twig', [], new Response('', 503));
}
```

---

## Interface super admin — Frontend

### `SuperAdminMaintenanceController`

```php
<?php

namespace App\Controller;

use App\Domain\Helper\ApiExceptionHandlerHelper;
use App\Domain\Helper\ApiHelper;
use App\Security\Exception\ApiException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/superadmin/maintenance', name: 'superadmin.maintenance.')]
#[IsGranted('ROLE_SUPER_ADMIN')]
final class SuperAdminMaintenanceController extends AbstractController
{
    public function __construct(
        private readonly ApiHelper $api,
        private readonly ApiExceptionHandlerHelper $apiExceptionHandler
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        try {
            $status = $this->api->get('/api/maintenance/status');
        } catch (ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e);
            if ($response) return $response;
        }

        return $this->render('superadmin/maintenance/index.html.twig', [
            'status' => $status ?? ['active' => false, 'message' => ''],
        ]);
    }

    #[Route('/toggle', name: 'toggle', methods: ['POST'])]
    public function toggle(Request $request): Response
    {
        try {
            $message = $request->request->get('message', '');
            $this->api->post('/api/maintenance/toggle', [
                'message' => $message ?: null,
            ]);
            $this->addFlash('success', 'Mode maintenance mis à jour avec succès');
        } catch (ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'superadmin.maintenance.index');
            if ($response) return $response;
        }

        return $this->redirectToRoute('superadmin.maintenance.index');
    }
}
```

### `superadmin/maintenance/index.html.twig`

```twig
{% extends 'base.html.twig' %}
{% block title %}Mode maintenance{% endblock %}
{% block body %}

<div class="flex items-start justify-between gap-4 mb-5">
    <div>
        <h1 class="text-4xl font-light tracking-[-0.55px] leading-[1.2]">Mode maintenance</h1>
        <p class="text-sm text-muted-foreground mt-1">
            Activez le mode maintenance pour bloquer l'accès à l'application
        </p>
    </div>
</div>

<div class="max-w-lg space-y-4">

    {# -- Statut actuel -- #}
    <div class="card p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold">Statut actuel</h2>
            {% if status.active %}
                <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium bg-orange-100 text-orange-800 dark:bg-orange-950 dark:text-orange-300">
                    🔧 Maintenance active
                </span>
            {% else %}
                <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-300">
                    ✅ Application opérationnelle
                </span>
            {% endif %}
        </div>

        {% if status.active %}
            <div class="rounded-lg border border-orange-200 bg-orange-50 dark:bg-orange-950/30 p-3 text-sm text-orange-700 dark:text-orange-400">
                <p class="font-medium mb-1">Message affiché aux utilisateurs :</p>
                <p>{{ status.message }}</p>
            </div>
        {% endif %}
    </div>

    {# -- Formulaire toggle -- #}
    <form method="POST" action="{{ path('superadmin.maintenance.toggle') }}" class="card p-6 space-y-4">

        {% if not status.active %}
        <div class="form-group">
            <label class="form-label" for="message">
                Message de maintenance (optionnel)
            </label>
            <textarea id="message" name="message" class="input" rows="3"
                      placeholder="Ex: Maintenance planifiée jusqu'à 18h00. Merci de votre compréhension."></textarea>
            <p class="text-xs text-muted-foreground mt-1">
                Ce message sera affiché aux utilisateurs pendant la maintenance.
            </p>
        </div>
        {% endif %}

        <div class="flex items-center gap-3">
            <button type="submit"
                    class="btn {{ status.active ? 'btn-success' : 'btn-warning' }}"
                    onclick="return confirm('{{ status.active ? 'Désactiver le mode maintenance ?' : 'Activer le mode maintenance ? Les utilisateurs seront bloqués.' }}')">
                {{ status.active ? '✅ Désactiver la maintenance' : '🔧 Activer la maintenance' }}
            </button>
        </div>

    </form>

    {% if status.active %}
    <div class="rounded-xl border border-orange-200 bg-orange-50 dark:bg-orange-950/30 p-4 text-sm text-orange-700 dark:text-orange-400">
        <p class="font-semibold mb-1">⚠️ Maintenance active</p>
        <p>Tous les utilisateurs (sauf vous) sont actuellement bloqués et voient la page de maintenance.</p>
    </div>
    {% endif %}

</div>

{% endblock %}
```

---

## Récapitulatif des deux approches

| | Fichier `.lock` (Backend) | Base de données (Frontend) |
|---|---|---|
| Activé par | Manuellement ou super admin via CLI | Super admin via interface |
| Résistant à la panne DB | ✅ Oui | ❌ Non |
| Interface graphique | ❌ Non | ✅ Oui |
| Utilisé pour | Bloquer les requêtes API | Bloquer les pages frontend |
| Super admin exempt | Via header bypass | Via `ROLE_SUPER_ADMIN` |

Les deux fonctionnent en parallèle — quand tu actives la maintenance via l'interface super admin, tu peux aussi créer le fichier `.lock` manuellement pour bloquer l'API. Quelle est la suite ?









Parfait. On code.

---

## Architecture

```
Backend  → stocke quels guides ont été complétés par utilisateur
Frontend → Driver.js déclenche le guide, appelle l'API pour marquer comme vu
```

---

## Backend

### Entité `UserGuide`

```php
<?php

namespace App\Entity;

use App\Repository\UserGuideRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserGuideRepository::class)]
#[ORM\UniqueConstraint(fields: ['userId', 'guide'])]
class UserGuide
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $userId;

    #[ORM\Column(length: 100)]
    private string $guide; // ex: 'billetterie', 'exploitation', 'stock'

    #[ORM\Column]
    private \DateTimeImmutable $completedAt;

    public function __construct(int $userId, string $guide)
    {
        $this->userId      = $userId;
        $this->guide       = $guide;
        $this->completedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getUserId(): int { return $this->userId; }
    public function getGuide(): string { return $this->guide; }
    public function getCompletedAt(): \DateTimeImmutable { return $this->completedAt; }
}
```

---

### Endpoint API Platform sur `User`

```php
// Dans l'entité User — deux nouvelles opérations

new Get(
    security: "is_granted('ROLE_USER')",
    uriTemplate: '/me/guides',
    provider: UserGuidesProvider::class,
    openapi: new Operation(
        summary: 'Guides complétés par l\'utilisateur',
        security: [['bearerAuth' => []]]
    )
),
new Post(
    security: "is_granted('ROLE_USER')",
    uriTemplate: '/me/guides/{guide}/complete',
    provider: CorbeilleEmptyProvider::class,
    processor: CompleteGuideProcessor::class,
    openapi: new Operation(
        summary: 'Marquer un guide comme complété',
        security: [['bearerAuth' => []]]
    )
),
```

---

### `UserGuidesProvider`

```php
<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use App\Repository\UserGuideRepository;
use Symfony\Bundle\SecurityBundle\Security;

class UserGuidesProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        private UserGuideRepository $userGuideRepository
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        /** @var User $user */
        $user   = $this->security->getUser();
        $guides = $this->userGuideRepository->findBy(['userId' => $user->getId()]);

        return array_map(
            fn($g) => [
                'guide'       => $g->getGuide(),
                'completedAt' => $g->getCompletedAt()->format('Y-m-d H:i'),
            ],
            $guides
        );
    }
}
```

---

### `CompleteGuideProcessor`

```php
<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use App\Entity\UserGuide;
use App\Repository\UserGuideRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class CompleteGuideProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $em,
        private UserGuideRepository $userGuideRepository
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var User $user */
        $user  = $this->security->getUser();
        $guide = $uriVariables['guide'] ?? null;

        if (!$guide) return null;

        // Idempotent — on ne crée pas en doublon
        $existing = $this->userGuideRepository->findOneBy([
            'userId' => $user->getId(),
            'guide'  => $guide,
        ]);

        if (!$existing) {
            $userGuide = new UserGuide($user->getId(), $guide);
            $this->em->persist($userGuide);
            $this->em->flush();
        }

        return ['guide' => $guide, 'completed' => true];
    }
}
```

---

### Migration

```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

---

## Frontend

### Installation Driver.js

```bash
npm install driver.js
```

---

### `guide.ts` — module réutilisable

```typescript
import { driver, DriveStep } from 'driver.js'
import 'driver.js/dist/driver.css'

const GUIDES_ENDPOINT = '/auth/token' // ton endpoint token existant

async function getCompletedGuides(): Promise<string[]> {
    try {
        const token = await fetchToken()
        const res   = await fetch('/api/me/guides', {
            headers: { Authorization: `Bearer ${token}` }
        })
        if (!res.ok) return []
        const data = await res.json()
        return (data['hydra:member'] ?? data).map((g: any) => g.guide)
    } catch {
        return []
    }
}

async function markGuideComplete(guide: string): Promise<void> {
    try {
        const token = await fetchToken()
        await fetch(`/api/me/guides/${guide}/complete`, {
            method: 'POST',
            headers: {
                Authorization: `Bearer ${token}`,
                'Content-Type': 'application/json',
            }
        })
    } catch {
        // silencieux
    }
}

async function fetchToken(): Promise<string> {
    const res  = await fetch('/auth/token')
    const data = await res.json()
    return data.token
}

export async function startGuide(
    guideName: string,
    steps: DriveStep[],
    options: { auto?: boolean } = {}
): Promise<void> {
    const completed = await getCompletedGuides()
    const isCompleted = completed.includes(guideName)

    // Si auto et déjà complété → on ne lance pas
    if (options.auto && isCompleted) return

    const driverObj = driver({
        showProgress:    true,
        showButtons:     ['next', 'previous', 'close'],
        nextBtnText:     'Suivant →',
        prevBtnText:     '← Précédent',
        doneBtnText:     'Terminer',
        progressText:    '__current__ / __total__',
        allowClose:      true,
        overlayColor:    'rgb(0, 0, 0)',
        overlayOpacity:  0.5,
        smoothScroll:    true,
        steps,
        onDestroyStarted: () => {
            // Marquer comme complété quand l'utilisateur ferme ou termine
            markGuideComplete(guideName)
            driverObj.destroy()
        },
    })

    driverObj.drive()
}
```

---

### Exemple de guide — module Billetterie

```typescript
// assets/js/guides/billetterie.ts
import { startGuide } from '../guide'
import type { DriveStep } from 'driver.js'

const steps: DriveStep[] = [
    {
        element: '#btn-new-ticket',
        popover: {
            title:       '🎟️ Créer un ticket',
            description: 'Cliquez ici pour créer un nouveau ticket pour un voyageur.',
            side:        'bottom',
            align:       'start',
        }
    },
    {
        element: '#ticket-table',
        popover: {
            title:       '📋 Liste des tickets',
            description: 'Retrouvez ici tous les tickets émis pour ce voyage. Vous pouvez filtrer et trier.',
            side:        'top',
        }
    },
    {
        element: '#btn-print-bordereau',
        popover: {
            title:       '🖨️ Imprimer le bordereau',
            description: 'Imprimez le bordereau récapitulatif de tous les tickets du voyage.',
            side:        'left',
        }
    },
]

// Démarrage automatique à la première visite
export function initBilleterieGuide(auto = false) {
    startGuide('billetterie', steps, { auto })
}
```

---

### Intégration dans les vues Twig

Dans `billetterie/index.html.twig` :

```twig
{# -- Bouton aide -- #}
<button type="button" id="btn-guide-billetterie" class="btn btn-secondary btn-sm">
    <svg class="h-4 w-4" aria-hidden="true"><use href="{{ asset('sprite.svg') }}#help"></use></svg>
    Guide
</button>

<script type="module">
    import { initBilleterieGuide } from '/build/guides/billetterie.js'

    // Auto au premier chargement
    initBilleterieGuide(true)

    // Bouton pour relancer manuellement
    document.getElementById('btn-guide-billetterie')
        .addEventListener('click', () => initBilleterieGuide(false))
</script>
```

---

### `webpack.config.js` — entrées des guides

```javascript
Encore
    // ...entrées existantes...
    .addEntry('guides/billetterie', './assets/js/guides/billetterie.ts')
    .addEntry('guides/exploitation', './assets/js/guides/exploitation.ts')
    .addEntry('guides/stock', './assets/js/guides/stock.ts')
    // ...
```

---

### Exemple de guide — module Exploitation

```typescript
// assets/js/guides/exploitation.ts
import { startGuide } from '../guide'
import type { DriveStep } from 'driver.js'

const steps: DriveStep[] = [
    {
        element: '#btn-new-voyage',
        popover: {
            title:       '🚌 Créer un voyage',
            description: 'Créez un nouveau voyage en définissant le trajet, le car et les horaires.',
            side:        'bottom',
        }
    },
    {
        element: '#voyage-table',
        popover: {
            title:       '📅 Liste des voyages',
            description: 'Gérez vos voyages ici. Filtrez par statut, date ou trajet.',
            side:        'top',
        }
    },
    {
        element: '#filter-statut',
        popover: {
            title:       '🔍 Filtrer par statut',
            description: 'Filtrez les voyages par statut : En cours, Terminé, Annulé...',
            side:        'bottom',
        }
    },
]

export function initExploitationGuide(auto = false) {
    startGuide('exploitation', steps, { auto })
}
```

---

## Réinitialiser les guides complétés

Pour permettre à l'utilisateur de réinitialiser ses guides depuis son profil :

### Endpoint

```php
new Post(
    security: "is_granted('ROLE_USER')",
    uriTemplate: '/me/guides/reset',
    provider: CorbeilleEmptyProvider::class,
    processor: ResetGuidesProcessor::class,
    openapi: new Operation(
        summary: 'Réinitialiser tous les guides',
        security: [['bearerAuth' => []]]
    )
),
```

### `ResetGuidesProcessor`

```php
<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use App\Entity\UserGuide;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class ResetGuidesProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $em
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var User $user */
        $user   = $this->security->getUser();
        $guides = $this->em->getRepository(UserGuide::class)->findBy([
            'userId' => $user->getId()
        ]);

        foreach ($guides as $guide) {
            $this->em->remove($guide);
        }
        $this->em->flush();

        return ['message' => 'Guides réinitialisés'];
    }
}
```

### Bouton dans `profil/index.html.twig`

```twig
<div class="card p-5">
    <h2 class="text-lg font-semibold mb-2">Guides d'utilisation</h2>
    <p class="text-sm text-muted-foreground mb-4">
        Réinitialisez les guides pour les revoir depuis le début à votre prochaine visite.
    </p>
    <form method="POST" action="{{ path('profil.reset_guides') }}">
        <button type="submit" class="btn btn-secondary btn-sm"
                onclick="return confirm('Réinitialiser tous les guides ?')">
            Réinitialiser les guides
        </button>
    </form>
</div>
```

### Route dans `ProfilController`

```php
#[Route('/profil/guides/reset', name: 'profil.reset_guides', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
public function resetGuides(): Response
{
    try {
        $this->api->post('/api/me/guides/reset');
        $this->addFlash('success', 'Guides réinitialisés avec succès');
    } catch (ApiException $e) {
        $this->addFlash('error', 'Erreur lors de la réinitialisation');
    }

    return $this->redirectToRoute('profil.index');
}
```

Quelle est la suite ?








C'est le comportement par défaut de Doctrine avec `@MappedSuperclass` — les colonnes de la classe parente apparaissent en premier dans la table.

Pour contrôler l'ordre des colonnes, tu peux utiliser `columnDefinition` ou plus simplement **réorganiser via une migration manuelle**, mais la solution propre dans Doctrine est d'utiliser l'attribut `#[ORM\Column]` avec l'option `columnDefinition` ... ce qui est verbeux.

**La vraie solution : `AttributeOverride` ou changer l'approche**

Doctrine ne fournit pas d'option native pour réordonner les colonnes d'un `MappedSuperclass`. Les colonnes du parent viennent toujours en premier.

Tes options :

---

**Option 1 — Accepter l'ordre et réorganiser via migration SQL**

C'est la plus simple. L'ordre des colonnes en base n'a aucun impact fonctionnel. Tu modifies la table manuellement :

```sql
ALTER TABLE ma_table MODIFY COLUMN created_at DATETIME AFTER derniere_colonne_metier;
```

À refaire à chaque nouvelle table, mais sans impact sur le code.

---

**Option 2 — Inverser la logique : trait au lieu de MappedSuperclass**

C'est l'approche recommandée quand l'ordre des colonnes compte. Tu transformes `EntityBase` en **trait** :

```php
trait EntityBaseTrait
{
    #[ORM\Column(name: "created_at", type: "datetime_immutable", nullable: true)]
    protected ?DateTimeImmutable $createdAt = null;

    // ... toutes les propriétés

    #[ORM\PrePersist]
    public function onPrePersist(): void { ... }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void { ... }
}
```

Puis dans chaque entité, tu places le `use` **après** tes propres propriétés :

```php
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class User
{
    #[ORM\Id]
    #[ORM\Column]
    private int $id;

    #[ORM\Column]
    private string $email;

    // ... tes colonnes métier

    use EntityBaseTrait; // ← placé en dernier = colonnes en dernier en DB
}
```

Doctrine respecte l'ordre de déclaration dans la classe, donc les colonnes du trait arrivent **après** les propriétés déclarées avant le `use`.

---

**Option 3 — Garder MappedSuperclass + `#[ORM\HasLifecycleCallbacks]` sur les enfants**

Si tu veux garder l'héritage de classe mais juste déplacer certaines colonnes clés (comme `id`) dans l'enfant, c'est structurellement impossible avec `MappedSuperclass` — l'ordre parent → enfant est figé.

---

**Recommandation**

Le **trait** (Option 2) est la solution la plus propre pour ton cas. Elle te donne le contrôle total sur l'ordre des colonnes sans SQL manuel, et les lifecycle callbacks fonctionnent correctement tant que `#[ORM\HasLifecycleCallbacks]` est sur l'entité concrète.

L'`abstract class` peut rester pour la logique non-Doctrine (méthodes utilitaires, etc.) si tu veux garder une hiérarchie, mais les propriétés mappées passent dans le trait.









Parfait 🔥 on va construire un **système d’alertes intelligent** pour ton app — et là on passe en mode **produit pro**.

---

# 🚨 🎯 OBJECTIF DU MODULE ALERTES

Notifier automatiquement :

* 📦 stock faible
* 🚍 voyage complet / presque complet
* 🛠️ anomalies (optionnel plus tard)

---

# 🧠 🧩 1. CONCEPTION GLOBALE

On crée une entité centrale :

## 👉 `Alerte`

Elle servira pour **TOUT le système**

---

# 🧱 2. ENTITY `Alerte`

```php
#[ORM\Entity]
class Alerte extends EntityBase implements EntrepriseOwnedInterface
{
    #[ORM\Column(length: 50)]
    private string $type; // STOCK_FAIBLE, VOYAGE_COMPLET...

    #[ORM\Column(length: 255)]
    private string $message;

    #[ORM\Column(nullable: true)]
    private ?int $referenceId = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $referenceType = null;

    #[ORM\Column]
    private bool $isRead = false;

    #[ORM\Column]
    private int $identreprise;
}
```

---

# 🧠 TYPES D’ALERTES

```php
class AlerteType
{
    public const STOCK_FAIBLE = 'STOCK_FAIBLE';
    public const VOYAGE_COMPLET = 'VOYAGE_COMPLET';
    public const VOYAGE_BIENTOT_COMPLET = 'VOYAGE_BIENTOT_COMPLET';
}
```

---

# 🧠 🧩 3. SERVICE CENTRAL (TRÈS IMPORTANT)

👉 pour éviter du code partout

```php
class AlerteService
{
    public function __construct(private EntityManagerInterface $em) {}

    public function create(
        string $type,
        string $message,
        int $entrepriseId,
        ?int $referenceId = null,
        ?string $referenceType = null
    ): void {
        $alerte = new Alerte();
        $alerte
            ->setType($type)
            ->setMessage($message)
            ->setIdentreprise($entrepriseId)
            ->setReferenceId($referenceId)
            ->setReferenceType($referenceType);

        $this->em->persist($alerte);
    }
}
```

---

# 📦 4. ALERTES STOCK FAIBLE

---

## 🎯 RÈGLE

```txt
stock <= seuil
```

👉 ajoute dans `Piece` :

```php
private int $seuilAlerte = 5;
```

---

## 🧠 DANS TON `StockmouvementService`

👉 après chaque mouvement :

```php
if ($piece->getStock() <= $piece->getSeuilAlerte()) {

    $this->alerteService->create(
        AlerteType::STOCK_FAIBLE,
        "Stock faible pour {$piece->getLibelle()}",
        $entrepriseId,
        $piece->getId(),
        'PIECE'
    );
}
```

---

# 🚍 5. ALERTES VOYAGE

---

## 🎯 CAS 1 : VOYAGE COMPLET

Dans `TicketProcessor` :

```php
if ($voyage->getPlacesOccupees() == $voyage->getPlacesTotal()) {

    $this->alerteService->create(
        AlerteType::VOYAGE_COMPLET,
        "Voyage {$voyage->getCode()} complet",
        $entrepriseId,
        $voyage->getId(),
        'VOYAGE'
    );
}
```

---

## 🎯 CAS 2 : PRESQUE COMPLET

```php
$taux = ($voyage->getPlacesOccupees() / $voyage->getPlacesTotal()) * 100;

if ($taux >= 80) {
    $this->alerteService->create(
        AlerteType::VOYAGE_BIENTOT_COMPLET,
        "Voyage bientôt complet ({$taux}%)",
        $entrepriseId,
        $voyage->getId(),
        'VOYAGE'
    );
}
```

---

# 🧱 6. API PLATFORM (ENDPOINTS)

```php
#[ApiResource(
    security: "is_granted('IS_AUTHENTICATED_FULLY')",
    operations: [

        new GetCollection(
            security: "is_granted('VOIR', 'Alerte')"
        ),

        new Get(),

        new Patch(
            security: "is_granted('MODIFIER', object)"
        )
    ]
)]
```

---

# 📊 7. DASHBOARD "ALERTES NON LUES"

👉 endpoint :

```http
GET /alertes?isRead=false
```

---

## 🔥 BONUS FILTRE

```php
#[ApiFilter(SearchFilter::class, properties: [
    'isRead' => 'exact',
    'type' => 'exact'
])]
```

---

# 🧱 8. MARQUER COMME LU

👉 PATCH :

```json
{
  "isRead": true
}
```

---

# 💡 9. AMÉLIORATIONS PRO

---

## 🔥 1. Éviter spam alertes

👉 ex stock faible → une seule alerte active

```php
check if already exists non lue
```

---

## 🔥 2. Priorité

```php
priority (LOW, MEDIUM, HIGH)
```

---

## 🔥 3. Notification temps réel

* WebSocket
* ou polling `/alertes`

---

## 🔥 4. Notification WhatsApp / Email

👉 futur upgrade 🚀

---

# 🧠 CONCLUSION

Tu viens de créer :

## 🚨 système d’alertes complet

* 📦 stock
* 🚍 voyages
* 📊 dashboard
* 🔒 multi-entreprise

---

# 🚀 PROCHAINE ÉTAPE

👉 maintenant tu peux faire :

## 📊 DASHBOARD GLOBAL

* recettes
* voyages
* pannes
* top pièces

---

Dis-moi :
👉 **"dashboard"** et on fait un dashboard digne d’une startup 🚀🔥







## 2. EntityBase

> Les timestamps sont gérés par les lifecycle callbacks Doctrine.
> `createdBy`, `updatedBy`, `deletedBy` sont alimentés par `EntityAuditSubscriber`.

## 12. EntityAuditSubscriber

> Doctrine n'a pas accès au container dans les lifecycle callbacks des entités. On utilise un event subscriber pour peupler `createdBy`, `updatedBy`, `deletedBy`.

```php
// src/EventSubscriber/EntityAuditSubscriber.php
<?php

namespace App\EventSubscriber;

use App\Entity\EntityBase;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Bundle\SecurityBundle\Security;

#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
#[AsDoctrineListener(event: Events::preRemove)]
class EntityAuditSubscriber
{
    public function __construct(private readonly Security $security) {}

    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof EntityBase) {
            return;
        }
        $identifier = $this->security->getUser()?->getUserIdentifier();
        if ($identifier) {
            $entity->setCreatedBy($identifier);
            $entity->setUpdatedBy($identifier);
        }
    }

    public function preUpdate(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof EntityBase) {
            return;
        }
        $identifier = $this->security->getUser()?->getUserIdentifier();
        if ($identifier) {
            $entity->setUpdatedBy($identifier);
        }
    }

    public function preRemove(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof EntityBase) {
            return;
        }
        $identifier = $this->security->getUser()?->getUserIdentifier();
        if ($identifier) {
            $entity->setDeletedBy($identifier);
            $entity->setDeletedAt(new \DateTimeImmutable());
        }
    }
}
```
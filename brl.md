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
SycaPay, Jèko, GeniusPay, AdjeminPay

- Amélioration, nouvelle fonctionnalité 
    - client next(implémenté un module) en utilisant les meilleurs pratiques
    - Géolocalisation pour le suivi des cars en temps réel
    - Notifications : SMS(départ imminent, retard, arrivée)
    - On veut que l'application soit le plus simple à utiliser

- Courrier -> GroupCourrier avec un code pour savoir qu'on a mis ce lot de courrier dans un voyage
- Les moyens de paiement ou règlement pour les tickets, courrier ou bagages pour savoir par quoi les clients paient le plus souvent
    > Sur la page de création de ticket :: Mobile money -> Ouvre panneau -> Mtn, Wave.. -> puis on saisi l'indentifiant du paiement dans la base de données
- Courrier: séparer leur chiffre d'affaire avec celle de la société

- Reservation de ticket en ligne
    - Les passagers réservent via mobile
    - La réservation temporaire.. blocage de siège avant paiement

- Ticket
    - !! carte de fidélité pour les meilleurs clients basé sur le numéro de téléphone du client ou une vrai entité
        Client
        - nom
        - contact
        - numero_carte
        - points

- Place: de la droite vers la gauche et 6 places derrière
    3 4 5 2 1
    6 places
    - Vert: libre au départ, vendu: rouge, libérer à nouveau gris, ainsi de suite

- Le grisage des sièges
    - Sol 1
        - Dans la partie création de ticket, le faite de grisés les sièges en cas de vente ne conçernent que la gare qui vend, si le client ne décend pas à une gare intermédiaire ex: gare 2 elle doit voir les sièges grisé, les autres gares du même trajet peuvent vendre et verront en grisés les sièges qu'ils ont vendu
            > Ex: Si la gare 1 vend 30/64, la gare 2 peut vendre les 64 places du départ sans tenir compte des places vendu à la gare précédente, le car arrive à la gare 2 des clients vont décendre et les autres montes au cas ou c'est rempli ou il y'a 2 tickets pour un même siège la gare 2 programme un autre départ avec un autre car pour les clients restants, vend les tickets et devient la gare de provenance du nouveau départ
                > le problème : Tu dis que chaque gare voit grisés uniquement les sièges qu'elle a elle-même vendus. Mais dans ce cas, comment le commercial à bord sait quels sièges sont physiquement occupés quand il est entre deux gares ?

    - Sol 2 : Je me dis qu'une solution est de grisé un siège selon la gare de destination du client pour toutes les gares du trajet :: Actuel


- 
déclarer comme perdu même lorsque le voyage est clôturer

- Un polling en temps réel qui actualise l'affichage des sièges de la partie vente de tickets :: comment .. en temps réel
- On vas associé le ticket du client à ses bagages comme ça le bagage aura pour provenance et destination celui du client
- Si un car est déjà en dépannage tant que le dépannage n'est pas clôturer on ne doit pas pouvoir le mettre dans nouveau dépannage

- Guide utilisateur
- Explication et fonctionnement des modules importants de l'application


- Table Ville sur Gare Tracé ce qui se passe dans l'application qui a fais quoi

    - !! on vas la remise sur ticket (une réduction appliquée au prix normal du voyage) puis connaître le béfinicière, contactbeneficiaire
        le système calcule un tarif de base,
        puis applique éventuellement une réduction,
        pour obtenir le montant final payé.
        - Beneficiaire -> soit on le crée ou on le choisi avec le select..
            - Peut être un client, enfants.. qui vient toujours ou un corsaire(celui qui envoi le client) reçois identreprise et visible dans toutes les gares

- Le tirage du borderau à la gare d'arrivée va automatiquement clôturer le voyage pour la gare de destination et réceptioné le voyage pour une gare intermédiaire

- Dans un voyage on un chauffeur et un commercial, le commercial est lié à la gare de provenance, le commercial peut vendre les tickets en route via un TPE dans le cas où un client monte en route, si un client déscend en route et non dans une gare intermédiaire le commercial doit avoir la possibilité de dégrisé un siège pour la revendre en appellant l'administrateur de la gare de provenance pour lui demander de dégrisé le siège pour qu'il le revend ou autre.
    > Mais le commercial pose problème, comment sa carte s'actualise s'il est dans une gare intermédiaire lorsqu'ils vont vendre sièges

- Dépenses : 2 types (Dépense générale et gare)
    Objetdepense -> libelle          Objetdepensegare..
    Depense                          Depensegare..
        objetdepense -> vers Objetdepense
        date
        montant
        detail


- Ajouter le codeticket dans bagage, aussi le bagage aura pour destination celui du ticket du client.. :: ajouter la saisie de garedepart sur bagage
    > bagage/_form.html.twig : pour un agent rattaché, la liste Gare de descente n'affiche que les arrêts après sa gare (évite l'erreur « descente avant le départ »), avec la mention « Départ depuis votre gare (X) ».
    > Ajouter gare depart dans le formulaire
- Historique Detailcar du genre si un car a été changer en cours de route
    > Les petits détails du genre on veut pouvoir tout connaître dans l'application
- Si une gare intermédiare receptionne un voyage .. heure de départ de sa gare




Pré-requis : pour gérer des rôles, l'acteur de gare doit avoir une permission explicite Role_* — Role n'est volontairement pas dans le bypass de l'admin de gare (sinon il pourrait s'auto-déléguer la gestion des rôles en chaîne). C'est donc l'admin entreprise qui décide de lui ouvrir ce droit. ou Role dans le bypass
Si une gare intermédiare doit créer un voyage il peut créer une ligne ou utiliser la ligne qui passe par lui actuellement



- Plusieurs codes générés se basent sur des `count(...) + 1` ; cela peut poser des collisions en cas de créations concurrentes.
- On vas mettre en place un verrou pessimiste pour la partie :
    **Concurrence sur le stock** : `stockinitial` est lu puis écrit sans verrou ; deux sorties simultanées pourraient passer sous zéro


### 8.3 🟠 ÉLEVÉ — IDOR inter-entreprises sur `Detailpersonnel` et `Detailcourrier`

Ces deux ressources **n'implémentent pas `EntrepriseOwnedInterface`** et n'ont pas de champ `identreprise` → `EntrepriseScopeExtension` ne les filtre **pas**. Et le `PermissionVoter` ne vérifie que *« l'utilisateur possède la permission dans SON entreprise »*, jamais que *l'objet ciblé appartient à son entreprise*. Résultat : isolation tenant absente sur ces entités.

- **`Detailpersonnel`** (`Detailpersonnel.php:22-23`) : l'opération `Delete` a son `security: "is_granted('SUPPRIMER', object)"` **commenté**, elle retombe donc sur le `security` de classe `IS_AUTHENTICATED_FULLY`. → **N'importe quel utilisateur authentifié, de n'importe quelle entreprise, peut mettre en corbeille n'importe quel `detailpersonnel` par son id** (`DELETE /api/detailpersonnels/{id}`). Double problème : pas de permission métier **et** pas de scope entreprise.
- **`Detailcourrier`** : CRUD complet avec permissions `VOIR/CREER/MODIFIER/SUPPRIMER`, mais sans scope entreprise → un utilisateur de l'entreprise A ayant la permission peut **lire/modifier/supprimer les `detailcourrier` de l'entreprise B** par id, et `GetCollection` **liste les données de toutes les entreprises**.

→ *Correctif : faire implémenter `EntrepriseOwnedInterface` + `IdEntrepriseTrait` à ces entités (comme leurs parents), pour que `EntrepriseScopeExtension` les filtre automatiquement ; alimenter `identreprise` à la création (via `EntrepriseInjectionProcessor` / le processor parent). Et **décommenter** la sécurité `SUPPRIMER` sur `Detailpersonnel::Delete`.* À auditer pareillement : toute autre ressource exposée sans `EntrepriseOwnedInterface` (ex. `Siege`, cf. §8.5).

- **`Siege` sans scope entreprise** — `SiegeStateProvider` fait `findBy(['car' => $carId])` à partir du paramètre `?car=` sans vérifier que le car (et le voyage) appartiennent à l'entreprise de l'appelant. Un utilisateur peut lire le **plan de salle et l'occupation (tickets)** des cars d'une autre entreprise en devinant des ids. `Siege` n'implémente pas `EntrepriseOwnedInterface` (il a `identreprise` mais le provider ne s'en sert pas). → Filtrer par `identreprise` dans le provider.






### Opus

- **#1 (scoping par gare)** : reporté — sera traité plus tard avec le `GareScopeExtension`, car il dépend d'autres notions à intégrer. (point #4 sera couvert par ce même filtre.)
- **Tarification = matrice complète O-D** : tout couple (montée, descente) le long de la ligne a son tarif (Abidjan→Bouaké, Yamoussoukro→Korhogo, Bouaké→Korhogo…).
- **Places = capacité par segment** : un siège se libère à la descente et est revendable pour les tronçons suivants.

### 9.4 Cœur du système : disponibilité des sièges par tronçon

On modélise chaque ticket comme un **intervalle semi-ouvert** `[ordre(montée), ordre(descente))` sur le siège.

```
Arrêts :   Abidjan(0)   Yamoussoukro(1)   Bouaké(2)   Korhogo(3)
Siège 12 :  ●───────────────────────────● B vendu              [0,2)  Abidjan→Bouaké
                                          ●───────────────────● même siège revendable [2,3) Bouaké→Korhogo
```

**Test de chevauchement** de deux intervalles `[a,b)` et `[c,d)` :
```
chevauchement  ⟺  a < d  ET  c < b
```
- A=[0,2) (Abidjan→Bouaké) et B=[2,3) (Bouaké→Korhogo) : `0<3` ET `2<2`(faux) ⇒ **pas** de chevauchement → même siège OK.
- A=[0,2) et C=[1,3) (Yamoussoukro→Korhogo) : `0<3` ET `1<2` ⇒ chevauchement → siège indisponible pour C.

**Vente d'un ticket (siège S, montée m, descente d) :**
1. `m`, `d` sont des arrêts de `voyage.ligne` et `ordre(m) < ordre(d)`.
2. `TarifLigne(ligne, m, d)` existe → fixe le `prix`.
3. Aucun ticket actif sur `(voyage, S)` dont l'intervalle chevauche `[ordre(m), ordre(d))`.
4. Verrou pessimiste sur le `voyage` (déjà esquissé dans `TicketProcessor`) pour éviter le double-booking concurrent.

Avec des **sièges nommés**, la capacité par tronçon est garantie automatiquement : on ne peut pas affecter un siège dont l'intervalle chevauche un ticket existant ⇒ pas besoin d'un compteur global.

**`SiegeStateProvider` (à refondre)** : prend désormais `voyage` + `montee` + `descente` (le tronçon demandé). Un siège est `LIBRE` pour ce tronçon si aucun ticket du voyage ne le chevauche, sinon `OCCUPE`. Sans tronçon fourni, la disponibilité n'a plus de sens « globale » → l'UI doit d'abord demander montée/descente.

### 9.5 Impact par module

| **Courrier** | Ajouter `gareDescente` (gare d'arrivée du colis) ; garder `garedepart`=montée. Tarif **inchangé** (grille valeur/poids). ⚠️ `VoyageClotureSatutSubscriber` ne flippe `EN_TRANSIT→RECEPTIONNE` qu'à la clôture (terminus) : un colis destiné à Bouaké devrait être réceptionnable **à l'arrêt Bouaké**, pas seulement au terminus → décision (cf. 9.7). |
| **Bagage** | Ajouter `gareDescente`. Tarif **inchangé** (poids). |
| **Bordereau** | Chauffeur : afficher montée/descente par ticket/colis/bagage. Bordereau de gare : `gare` d'émission = gareMontee, logique de filtre conservée. |
| **Tableau de bord** | « Taux de remplissage » n'est plus `placesoccupees/placestotal` mais **par tronçon** (ex. occupation max ou moyenne sur les segments). Recette billetterie = somme des `prix` (inchangé, prix simplement variables). |

### 9.7 Points de vigilance / décisions restantes
1. **Réception courrier par arrêt** : v1 simple = réception au terminus (clôture) ; v2 = transition par arrêt atteint. À trancher.

## 14. Implémentation — métier courrier : réception à l'arrêt (faite le 2026-06-11)

> Décision #2 appliquée : un colis destiné à un **arrêt intermédiaire** arrive avant le terminus → il est réceptionné **à sa gare d'arrivée**, pas à la clôture du voyage.

### 14.1 Modifié / créé
- **`ReceptionnerCourrierProcessor`** (nouveau) : `EN_TRANSIT → RECEPTIONNE`, garde sur le statut, `updatedBy/updatedAt`. (Restriction « agent de la gare d'arrivée » laissée en commentaire pour le futur `GareScopeExtension`.)
- **`Courrier`** : nouvel endpoint `PATCH /api/courriers/{id}/receptionner` (sécurité `is_granted('MODIFIER', object)`), placé avant `livrer`. Flux complet : `EN_ATTENTE → EN_TRANSIT → RECEPTIONNE` (à l'arrêt) `→ LIVRE` (au destinataire).
- **`VoyageClotureSatutSubscriber`** : à la clôture, n'auto-réceptionne plus que les colis **destinés au terminus** (`garearrivee == ligne.gareterminus`). Repli legacy conservé : voyage sans ligne ou colis sans `garearrivee` → comportement historique (auto-réception de tous). Les colis intermédiaires passent par l'endpoint manuel.

### 14.3 Raffinements backend laissés de côté (non bloquants)
- **Bagage** : reste livré (`LIVRE`) à la clôture (le bagage voyage avec le passager ; pas d'événement « descente passager », pas d'endpoint de remise bagage). Affinable plus tard si besoin (remise à `garedescente`).
- Affichage montée/descente dans les **bordereaux** (DTOs `Output/Bordereau/*`) — cosmétique, étape frontend/output.

### 19.1 Flux complet désormais opérationnel (frontend + backend)
1. Créer une **ligne** (arrêts ordonnés + grille tarifaire O-D) — UI `/ligne/nouveau`.
2. Créer un **voyage** sur cette ligne — UI `/voyage/nouveau`.
3. Affecter un car, puis **vendre des tickets par tronçon** (montée/descente, prix matrice, sièges par segment) — UI `/ticket/nouveau`.
4. Courrier : réception **à l'arrêt** (`/api/courriers/{id}/receptionner`) ou ici on pourrais faire un receptionné sur voyage par la gare intermédiare de destination






- - 
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









- Tarifligne

# Revenir au modèle `TarifLigne` — code complet

Guide **prêt à coller** pour repasser de la grille tarifaire **globale** (`Tarif` gare→gare) au modèle
**`TarifLigne`** (un tarif par couple de gares **et par ligne**).

> Rappel du compromis : `TarifLigne` réintroduit la duplication du prix d'un segment partagé par plusieurs
> lignes. À ne faire que si tu veux réellement des prix **différents selon la ligne**.

---

# BACKEND (`BK-Transport`)

## 1. `src/Entity/TarifLigne.php` (NOUVEAU fichier)

```php
<?php

namespace App\Entity;

use App\Repository\TarifLigneRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TarifLigneRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_tarifligne', columns: ['ligne_id', 'garedepart_id', 'garearrivee_id'])]
class TarifLigne
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['read:Ligne', 'read:Ligne:item'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'tariflignes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Ligne $ligne = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['read:Ligne', 'read:Ligne:item'])]
    private ?Gare $garedepart = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['read:Ligne', 'read:Ligne:item'])]
    private ?Gare $garearrivee = null;

    #[ORM\Column]
    #[Groups(['read:Ligne', 'read:Ligne:item'])]
    #[Assert\Positive(message: 'Le montant doit être strictement positif')]
    private ?int $montant = null;

    #[ORM\Column(nullable: true)]
    private ?int $identreprise = null;

    public function getId(): ?int { return $this->id; }

    public function getLigne(): ?Ligne { return $this->ligne; }
    public function setLigne(?Ligne $ligne): static { $this->ligne = $ligne; return $this; }

    public function getGaredepart(): ?Gare { return $this->garedepart; }
    public function setGaredepart(?Gare $garedepart): static { $this->garedepart = $garedepart; return $this; }

    public function getGarearrivee(): ?Gare { return $this->garearrivee; }
    public function setGarearrivee(?Gare $garearrivee): static { $this->garearrivee = $garearrivee; return $this; }

    public function getMontant(): ?int { return $this->montant; }
    public function setMontant(int $montant): static { $this->montant = $montant; return $this; }

    public function getIdentreprise(): ?int { return $this->identreprise; }
    public function setIdentreprise(?int $identreprise): static { $this->identreprise = $identreprise; return $this; }
}
```

> `TarifLigne` est volontairement une entité **simple** (pas de `EntityBase`/soft-delete) : c'est de la
> config, recréée à chaque édition de ligne (orphanRemoval). Elle n'a **pas** d'`ApiResource` : elle est
> gérée via `LigneInput`/`LigneProcessor` et lue via `read:Ligne`.

## 2. `src/Repository/TarifLigneRepository.php` (NOUVEAU fichier)

```php
<?php

namespace App\Repository;

use App\Entity\TarifLigne;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TarifLigne>
 */
class TarifLigneRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TarifLigne::class);
    }

    /** Prix d'un segment (garedepart → garearrivee) POUR une ligne donnée. */
    public function findMontant(int $ligneId, int $gareDepartId, int $gareArriveeId, int $entrepriseId): ?TarifLigne
    {
        return $this->findOneBy([
            'ligne' => $ligneId,
            'garedepart' => $gareDepartId,
            'garearrivee' => $gareArriveeId,
            'identreprise' => $entrepriseId,
        ]);
    }
}
```

## 3. `src/Entity/Ligne.php` (MODIF : ajouter la collection `tariflignes`)

Ajouter la propriété (après la collection `$arrets`, vers la ligne 134) :

```php
    /**
     * @var Collection<int, TarifLigne>
     */
    #[ORM\OneToMany(targetEntity: TarifLigne::class, mappedBy: 'ligne', cascade: ['persist'], orphanRemoval: true)]
    #[Groups(['read:Ligne', 'read:Ligne:item'])]
    private Collection $tariflignes;
```

Dans le **constructeur** :

```php
    public function __construct()
    {
        $this->arrets = new ArrayCollection();
        $this->voyages = new ArrayCollection();
        $this->tariflignes = new ArrayCollection(); // <-- ajouter
    }
```

Ajouter les méthodes (par ex. après `removeArret`) :

```php
    /**
     * @return Collection<int, TarifLigne>
     */
    public function getTariflignes(): Collection
    {
        return $this->tariflignes;
    }

    public function addTarifligne(TarifLigne $tarifligne): static
    {
        if (!$this->tariflignes->contains($tarifligne)) {
            $this->tariflignes->add($tarifligne);
            $tarifligne->setLigne($this);
        }
        return $this;
    }

    public function removeTarifligne(TarifLigne $tarifligne): static
    {
        if ($this->tariflignes->removeElement($tarifligne)) {
            if ($tarifligne->getLigne() === $this) {
                $tarifligne->setLigne(null);
            }
        }
        return $this;
    }
```

> `TarifLigne` est dans le même namespace `App\Entity` → pas d'import à ajouter.

## 4. `src/Entity/Dto/LigneInput.php` (MODIF : ajouter `tarifs`)

```php
    /**
     * Grille tarifaire : [['garedepart' => 12, 'garearrivee' => 7, 'montant' => 8000], ...]
     * @var array<int, array{garedepart: int, garearrivee: int, montant: int}>
     */
    #[Groups(['write:LigneInput'])]
    public array $tarifs = [];
```

## 5. `src/State/LigneProcessor.php` (MODIF)

**a.** Dans le bloc `Patch`, supprimer les anciens `tariflignes` AVANT recréation (juste après la
suppression des arrêts, avant le `$this->em->flush();`) :

```php
            // Hard delete des anciens tarifs de ligne (config, recréée)
            foreach ($ligne->getTariflignes()->toArray() as $tl) {
                $this->em->remove($tl);
            }
            $ligne->getTariflignes()->clear();
```

**b.** Après l'appel `$this->handleArrets(...)`, récupérer la map des ordres et appeler `handleTarifs` :

```php
        $ordreParGare = $this->handleArrets($ligne, $data->arrets, $entrepriseId);
        $this->handleTarifs($ligne, $data->tarifs, $ordreParGare, $entrepriseId);
```

> `handleArrets` retourne déjà `array<int,int> gareId => ordre` — on s'en sert pour valider que chaque
> tarif relie deux arrêts existants dans le bon sens.

**c.** Ajouter la méthode `handleTarifs` :

```php
    /**
     * Crée les TarifLigne à partir de l'input, en validant que chaque couple est constitué
     * d'arrêts de la ligne et orienté dans le sens du trajet (départ avant arrivée).
     *
     * @param array<int, array{garedepart:int, garearrivee:int, montant:int}> $tarifs
     * @param array<int,int> $ordreParGare  map gareId => ordre
     */
    private function handleTarifs(Ligne $ligne, array $tarifs, array $ordreParGare, int $entrepriseId): void
    {
        $vus = [];
        foreach ($tarifs as $t) {
            $departId = (int) ($t['garedepart'] ?? 0);
            $arriveeId = (int) ($t['garearrivee'] ?? 0);
            $montant = (int) ($t['montant'] ?? 0);

            if ($montant <= 0) {
                throw new BadRequestHttpException('Le montant d\'un tarif doit être strictement positif');
            }
            if (!isset($ordreParGare[$departId], $ordreParGare[$arriveeId])) {
                throw new BadRequestHttpException('Un tarif référence une gare qui n\'est pas un arrêt de la ligne');
            }
            if ($ordreParGare[$departId] >= $ordreParGare[$arriveeId]) {
                throw new BadRequestHttpException('Un tarif doit aller d\'un arrêt vers un arrêt situé après lui');
            }
            $key = $departId . '-' . $arriveeId;
            if (isset($vus[$key])) {
                throw new BadRequestHttpException('Un couple de gares est en doublon dans la grille tarifaire');
            }
            $vus[$key] = true;

            $tarifLigne = new TarifLigne();
            $tarifLigne
                ->setGaredepart($this->gareRepository->find($departId))
                ->setGarearrivee($this->gareRepository->find($arriveeId))
                ->setMontant($montant)
                ->setIdentreprise($entrepriseId);
            $ligne->addTarifligne($tarifLigne);
        }
    }
```

**d.** Ajouter l'import en tête de fichier :

```php
use App\Entity\TarifLigne;
```

## 6. `src/State/TicketProcessor.php` (MODIF : prix par ligne)

**a.** Imports : remplacer

```php
use App\Repository\TarifRepository;
```

par

```php
use App\Repository\TarifLigneRepository;
```

**b.** Constructeur : remplacer le paramètre

```php
        private TarifRepository $tarifRepository
```

par

```php
        private TarifLigneRepository $tarifLigneRepository
```

**c.** Résolution du prix (étape 4) : remplacer

```php
        $tarif = $this->tarifRepository->findMontant($monteeId, $descenteId, $entrepriseId);
```

par

```php
        $tarif = $this->tarifLigneRepository->findMontant($ligne->getId(), $monteeId, $descenteId, $entrepriseId);
```

> `$ligne` est déjà disponible dans le processor. `$tarif->getMontant()` reste inchangé.

## 7. `src/Entity/Data/CorbeilleRegistry.php` (MODIF, optionnel)

Si tu **gardes** `TarifLigne` dans la corbeille — mais comme `TarifLigne` n'a **pas** de soft-delete, tu
peux simplement **retirer** la ligne `'tarif' => Tarif::class` (si tu supprimes l'entité globale) et ne
rien ajouter. Si tu veux gérer la corbeille de `TarifLigne`, il faut d'abord lui donner `EntityBase`.

```php
// Retirer si on supprime l'entité globale :
//   use App\Entity\Tarif;   ← supprimer l'import
//   'tarif' => Tarif::class, ← supprimer l'entrée
```

## 8. Suppression de l'entité globale `Tarif` (si tu l'abandonnes)

Supprimer : `src/Entity/Tarif.php`, `src/Repository/TarifRepository.php`, `src/State/TarifProcessor.php`,
et retirer `Tarif` de `CorbeilleRegistry`. (Sinon, la garder en parallèle ne gêne pas.)

## 9. Migration (NOUVELLE) — `src/migrations/VersionXXXXXXXXXXXXXX.php`

`php bin/console make:migration` puis remplacer le contenu par :

```php
public function up(Schema $schema): void
{
    // 1. Recréer la table tarif_ligne (schéma d'origine, cf. Version20260611213840)
    $this->addSql('CREATE TABLE tarif_ligne (id INT AUTO_INCREMENT NOT NULL, montant INT NOT NULL, identreprise INT DEFAULT NULL, ligne_id INT NOT NULL, garedepart_id INT NOT NULL, garearrivee_id INT NOT NULL, INDEX IDX_8EC440735A438E76 (ligne_id), INDEX IDX_8EC4407316887400 (garedepart_id), INDEX IDX_8EC44073B466CD0 (garearrivee_id), UNIQUE INDEX UNIQ_tarifligne (ligne_id, garedepart_id, garearrivee_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    $this->addSql('ALTER TABLE tarif_ligne ADD CONSTRAINT FK_8EC440735A438E76 FOREIGN KEY (ligne_id) REFERENCES ligne (id)');
    $this->addSql('ALTER TABLE tarif_ligne ADD CONSTRAINT FK_8EC4407316887400 FOREIGN KEY (garedepart_id) REFERENCES gare (id)');
    $this->addSql('ALTER TABLE tarif_ligne ADD CONSTRAINT FK_8EC44073B466CD0 FOREIGN KEY (garearrivee_id) REFERENCES gare (id)');

    // 2. Fan-out : pour chaque ligne, chaque couple (arrêt amont, arrêt aval), reprendre le prix global
    $this->addSql('
        INSERT INTO tarif_ligne (ligne_id, garedepart_id, garearrivee_id, montant, identreprise)
        SELECT a1.ligne_id, a1.gare_id, a2.gare_id, t.montant, l.identreprise
        FROM arret a1
        JOIN arret a2 ON a2.ligne_id = a1.ligne_id AND a2.ordre > a1.ordre
        JOIN ligne l ON l.id = a1.ligne_id
        JOIN tarif t ON t.garedepart_id = a1.gare_id
                    AND t.garearrivee_id = a2.gare_id
                    AND t.identreprise = l.identreprise
                    AND t.deleted_at IS NULL
    ');

    // 3. (optionnel) supprimer la grille globale
    $this->addSql('DROP TABLE tarif');
}

public function down(Schema $schema): void
{
    // Best-effort inverse : recréer tarif depuis tarif_ligne (MAX par couple), puis drop tarif_ligne.
    $this->addSql('DROP TABLE tarif_ligne');
}
```

> ⚠️ Le fan-out ne crée des tarifs que pour les couples couverts par un `tarif` global. Vérifie ensuite
> qu'aucun segment vendable ne reste sans `TarifLigne`.

---

# FRONTEND (`FT-Transport`)

## 10. `assets/react/models/ligne.model.ts` (MODIF)

```ts
export interface TarifLigne {
    garedepart: GareRef
    garearrivee: GareRef
    montant: number
}

export interface Ligne {
    id: number
    codeligne: string
    libelle: string | null
    gareorigine: GareRef
    gareterminus: GareRef
    arrets: Arret[]
    tariflignes: TarifLigne[]   // <-- ajouter
    voyagesCount: number
}
```

## 11. `assets/react/controllers/Exploitation/LigneForm.tsx` (MODIF : remettre la grille)

**a.** Étendre l'interface initiale :

```ts
interface LigneInitial {
    id: number
    libelle: string | null
    heuredepart?: string | null
    arrets: { gare: GareRef; ordre: number }[]
    tariflignes?: { garedepart: { id: number }; garearrivee: { id: number }; montant: number }[]
}
```

**b.** State + helpers (sous les autres `useState`) :

```ts
    const pairKey = (a: number, b: number) => `${a}-${b}`

    const [fares, setFares] = useState<Record<string, string>>(() => {
        const init: Record<string, string> = {}
        ligne?.tariflignes?.forEach((t) => {
            init[pairKey(t.garedepart.id, t.garearrivee.id)] = String(t.montant)
        })
        return init
    })

    // Toutes les paires (amont < aval) des arrêts ordonnés
    const pairs = useMemo(
        () =>
            stops.flatMap((dep, i) =>
                stops.slice(i + 1).map((arr) => ({ depart: dep, arrivee: arr }))
            ),
        [stops]
    )

    const setFare = (key: string, value: string) =>
        setFares((prev) => ({ ...prev, [key]: value }))
```

**c.** Dans `handleSubmit`, construire `tarifs` et l'ajouter au payload (avant le `fetch`) :

```ts
        // Tarifs renseignés uniquement (montant > 0)
        const tarifs = pairs
            .map((p) => ({
                garedepart: p.depart.id,
                garearrivee: p.arrivee.id,
                montant: Number(fares[pairKey(p.depart.id, p.arrivee.id)] || 0),
            }))
            .filter((t) => t.montant > 0)

        const payload = {
            libelle: libelle.trim(),
            heuredepart: heuredepart || null,
            arrets: stops.map((s, idx) => ({ gare: s.id, ordre: idx })),
            tarifs, // <-- ajouter
        }
```

**d.** Carte « Grille tarifaire » (JSX, avant le bloc des boutons Annuler/Créer) :

```tsx
            {/* Grille tarifaire (matrice O-D) */}
            {stops.length >= 2 && (
                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="text-base">Grille tarifaire</CardTitle>
                        <CardDescription>Prix par tronçon (origine → arrêt suivant…).</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        {pairs.map((p) => {
                            const key = pairKey(p.depart.id, p.arrivee.id)
                            return (
                                <div key={key} className="flex items-center gap-3">
                                    <span className="flex-1 text-sm">
                                        {p.depart.libelle} <span className="text-muted-foreground mx-1">→</span> {p.arrivee.libelle}
                                    </span>
                                    <Input
                                        type="number"
                                        min={0}
                                        placeholder="FCFA"
                                        className="w-32"
                                        value={fares[key] ?? ""}
                                        onChange={(e) => setFare(key, e.target.value)}
                                    />
                                </div>
                            )
                        })}
                    </CardContent>
                </Card>
            )}
```

> `Card, CardHeader, CardTitle, CardDescription, CardContent, Input, useMemo` sont déjà importés dans
> `LigneForm.tsx`.

## 12. `src/Controller/LigneController.php` (MODIF : renvoyer `tarifs`)

Dans `new` **et** `edit`, ajouter `tarifs` au payload envoyé à l'API :

```php
            $ligne = $this->api->post('/api/lignes', [
                'libelle' => $payload['libelle'] ?? null,
                'heuredepart' => $payload['heuredepart'] ?? null,
                'arrets' => $payload['arrets'] ?? [],
                'tarifs' => $payload['tarifs'] ?? [],   // <-- ajouter
            ]);
```

(idem pour le `$this->api->patch('/api/lignes/' . $id, [...])` dans `edit`).

## 13. `templates/ligne/show.html.twig` (MODIF : remettre la carte)

Remplacer la note « grille tarifaire globale » par la carte qui itère `ligne.tariflignes` :

```twig
    <div class="card p-6 mb-3">
        <h3 class="text-lg font-bold mb-3">Grille tarifaire</h3>
        <ul class="space-y-2">
            {% for tarif in ligne.tariflignes %}
            <li class="flex items-center justify-between gap-3 text-sm">
                <span>
                    <span class="font-medium">{{ tarif.garedepart.libelle }}</span>
                    <span class="mx-1.5 text-muted-foreground">→</span>
                    <span class="font-medium">{{ tarif.garearrivee.libelle }}</span>
                </span>
                <span class="tabular-nums font-semibold">{{ tarif.montant|number_format(0, ',', ' ') }} FCFA</span>
            </li>
            {% else %}
            <li class="text-sm text-muted-foreground">Aucun tarif défini.</li>
            {% endfor %}
        </ul>
    </div>
```

## 14. Supprimer la gestion de la grille GLOBALE (si abandon de `Tarif`)

- Fichiers à supprimer : `src/Controller/TarifController.php`, `src/Form/TarifFormType.php`,
  `assets/react/controllers/Exploitation/TarifTable.tsx`, `assets/react/models/tarif.model.ts`,
  `templates/tarif/{index,new,edit,_form}.html.twig`.
- `templates/base.html.twig` : retirer le lien sidebar « Grille tarifaire » (`{% if is_granted('TARIF_VOIR') %}…`).

---

# VÉRIFICATIONS

```bash
# BK
php -l src/Entity/TarifLigne.php
php -l src/Repository/TarifLigneRepository.php
php -l src/State/LigneProcessor.php
php -l src/State/TicketProcessor.php
php bin/console doctrine:schema:validate --skip-sync     # mapping OK
php bin/console doctrine:mapping:info | grep TarifLigne  # [OK]

# FT
php bin/console lint:twig templates/ligne
npm run dev    # 0 erreur (8 warnings CSS pré-existants)
```

Test fonctionnel : créer une ligne + sa grille → vendre un ticket sur un tronçon → le prix vient bien du
`TarifLigne` de **cette** ligne.

---

# RÉFÉRENCES
- Migration `tarif_ligne → tarif` (à inverser) : `BK-Transport/migrations/Version20260612120000.php`
- Baseline contenant le schéma `tarif_ligne` d'origine : `BK-Transport/migrations/Version20260611213840.php`
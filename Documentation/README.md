# Flowerchino

**An open-source plant knowledge platform – structured, community-driven, hydroponics-first.**

Flowerchino documents plants worldwide following a standardized data structure with a strong focus on hydroponics cultivation parameters. All data is freely accessible via a public REST API. A community forum allows users to validate and improve entries over time. AI pre-fills new entries automatically when a plant is first requested.

---

## Table of Contents

- [Vision](#vision)
- [Features](#features)
- [Plant Data Structure](#plant-data-structure)
- [API](#api)
- [Community System](#community-system)
- [How It Works](#how-it-works)
- [Tech Stack](#tech-stack)
- [Relation to Florabase](#relation-to-florabase)
- [Roadmap](#roadmap)

---

## Vision

No existing platform combines authoritative botanical taxonomy with structured, growth-stage-specific hydroponics parameters. GBIF, iNaturalist, Trefle, and USDA cover taxonomy and ecology — but none provide the cultivation data that growers actually need: stage-differentiated pH, EC, VPD, PPFD, nutrient ratios, and grow system compatibility.

Flowerchino fills that gap: a machine-readable plant database built for growers, developers, and researchers — open, community-maintained, and API-accessible.

---

## Features

### Plant Database

Every plant entry follows a consistent, standardized structure including:
- Full botanical classification (Kingdom → Species) with IPNI and GBIF cross-references
- Common names in multiple languages
- Growth-stage-specific hydroponics parameters (see below)
- Grow system compatibility
- Quality grade and data completeness score

### Growth-Stage Hydroponics Parameters

Parameters are stored per growth stage — not as a single flat value per plant. This reflects how cultivation requirements change across the plant lifecycle.

**Stages:** `germinating` · `seedling` · `vegetative` · `flowering` · `fruiting` · `harvesting` · `dormant`

**Per-stage parameters:**

| Parameter | Unit | Example (Tomato, Fruiting) |
|---|---|---|
| pH | min/max | 5.8 – 6.2 |
| EC (Electrical Conductivity) | mS/cm | 2.5 – 4.0 |
| TDS (Total Dissolved Solids) | ppm | 1250 – 2000 |
| Water Temperature | °C | 18 – 22 |
| Dissolved Oxygen | mg/L | ≥ 8 |
| Air Temperature | °C | 22 – 28 |
| Humidity | % | 50 – 70 |
| VPD (Vapor Pressure Deficit) | kPa | 1.2 – 1.6 |
| PPFD (Light Intensity) | µmol/m²/s | 500 – 800 |
| DLI (Daily Light Integral) | mol/m²/day | 22 – 30 |
| Photoperiod | hours | 16 |
| Nitrogen / Phosphorus / Potassium | ppm | 80 / 60 / 280 |
| Calcium / Magnesium / Sulfur | ppm | 120 / 45 / 60 |

### Grow System Compatibility

Each plant entry documents which hydroponic systems it is suited for:

| System | Description |
|---|---|
| **DWC** | Deep Water Culture – roots suspended in oxygenated solution |
| **NFT** | Nutrient Film Technique – thin flowing film over roots |
| **Kratky** | Passive DWC – static reservoir, no pump |
| **Aeroponics** | Roots misted in air chamber |
| **Ebb & Flow** | Periodic flood and drain |
| **Drip** | Emitter-fed root zone |
| **Wicking** | Capillary uptake through growing medium |

### Public REST API

All plant data is queryable without authentication. The API is versioned (`/v1/`), returns consistent JSON envelopes, and supports filtering by growth stage, system, taxonomy, and full-text search.

Key endpoints:
```
GET /v1/plants                               # full plant list
GET /v1/plants/{id}/params?stage=flowering   # stage-specific parameters
GET /v1/plants/{id}/params?system=dwc        # system-filtered parameters
GET /v1/plants/search?q=tomato               # search by name
GET /v1/plants/by-slug/{slug}                # slug lookup
```

### AI Pre-fill

When a plant is requested that does not yet exist, it is automatically pre-populated by AI based on botanical and horticultural knowledge. The entry is marked as `draft` and immediately available — community members then review, correct, and verify the data.

### Community System

Each plant entry has a quality grade:

| Grade | Meaning |
|---|---|
| `draft` | AI pre-filled, not yet reviewed |
| `community` | Confirmed by ≥ 2 trusted contributors |
| `verified` | Reviewed and confirmed by a curator |

Users accumulate contribution points through forum activity and data improvements. Above a defined threshold, they unlock the ability to edit plant entries directly. All edits are tracked in a full edit history.

---

## Plant Data Structure

### Botanical Classification

```json
{
  "canonical_name": "Solanum lycopersicum",
  "scientific_name": "Solanum lycopersicum L.",
  "authorship": "L.",
  "common_names": [{ "lang": "en", "name": "Tomato" }, { "lang": "de", "name": "Tomate" }],
  "kingdom": "Plantae",
  "family": "Solanaceae",
  "genus": "Solanum",
  "species": "lycopersicum",
  "taxonomic_status": "accepted",
  "ipni_id": "320035-2",
  "gbif_key": 5284517,
  "slug": "solanum-lycopersicum"
}
```

### Stage Parameters (example)

```json
{
  "stage": "vegetative",
  "ph_min": 5.8, "ph_max": 6.2,
  "ec_min": 2.0, "ec_max": 3.0,
  "tds_min": 1000, "tds_max": 1500,
  "water_temp_min": 18, "water_temp_max": 22,
  "dissolved_oxygen_min": 8,
  "vpd_min": 0.8, "vpd_max": 1.2,
  "ppfd_min": 400, "ppfd_max": 600,
  "dli_min": 20, "dli_max": 25,
  "photoperiod_hours": 18,
  "n_ppm": 200, "p_ppm": 60, "k_ppm": 200,
  "ca_ppm": 180, "mg_ppm": 50, "s_ppm": 65
}
```

---

## API

Full documentation will be published at `Documentation/API/` once v1 is stable.

**Response format:**
```json
{ "status": "success", "data": {}, "error": null, "meta": {} }
```

Rate limiting headers: `X-RateLimit-Limit` · `X-RateLimit-Remaining` · `X-RateLimit-Reset`

---

## Community System

Flowerchino uses a contribution-threshold model inspired by iNaturalist's research grade system:

- Any user can suggest corrections or leave forum comments
- Suggestions from multiple independent users raise the data quality grade
- Users above the contribution threshold can apply edits directly
- Curators can flag, revert, and lock entries
- Full edit history is maintained for accountability

---

## How It Works

1. A user or application requests a plant by name, slug, IPNI ID, or GBIF key
2. If the plant exists, the full structured profile is returned immediately
3. If the plant does not exist, AI pre-fills a draft profile — available immediately
4. Community members review and refine the entry through the forum
5. Trusted contributors apply direct edits; curators verify high-importance entries
6. Third-party applications (like Florabase) consume the API for reference data

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.5 + Symfony |
| Database | MariaDB + Doctrine ORM |
| API | REST, versioned (`/v1/`) |
| Infrastructure | Docker Compose + Devcontainer |

---

## Relation to Florabase

[Florabase](https://github.com/tom-schorn/Florabase) is a private hydroponic plant tracking app. It allows users to log pH, EC, TDS measurements, growth phases, and plant observations for their own plants.

Flowerchino serves as the shared reference layer. Where Florabase tracks *your* plants, Flowerchino documents *all* plants. The parameter structure in Flowerchino is intentionally compatible with Florabase's data model — Florabase can pull species-level reference values directly from the Flowerchino API.

---

## Roadmap

- [ ] Core plant data model and Doctrine schema
- [ ] Growth-stage parameter tables
- [ ] REST API v1 (read)
- [ ] AI pre-fill integration
- [ ] REST API v1 (write + auth)
- [ ] User accounts and contribution tracking
- [ ] Community forum per plant entry
- [ ] Contribution threshold and edit permissions
- [ ] Quality grading system (draft → community → verified)
- [ ] Full API documentation
- [ ] IPNI / GBIF cross-reference import
- [ ] Open-source release

### V2+
- [ ] Fertilizer database (products, N-P-K compositions, manufacturer info)
- [ ] Grow setup guides (complete configurations per plant + system)
- [ ] Articles / editorial content

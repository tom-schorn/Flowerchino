# Flowerchino – Project Context for Claude

## What is Flowerchino?

Flowerchino is an open-source plant knowledge platform. It documents plants worldwide using a standardized, machine-readable data structure with a strong focus on hydroponics cultivation parameters. All data is accessible via a public REST API. A community forum allows users to contribute and – after reaching a defined contribution threshold – edit plant entries. New plant entries are pre-filled by AI when first requested.

Flowerchino is a companion to [Florabase](https://github.com/tom-schorn/Florabase), a private hydroponic plant tracking app (Flask + MySQL + React). Florabase tracks user-specific plant growth; Flowerchino provides the shared reference database behind it.

**Core gap this fills:** No existing platform (GBIF, iNaturalist, Trefle, USDA, POWO, Perenual) combines authoritative botanical taxonomy with structured, growth-stage-differentiated hydroponics parameters. This is a genuine, unoccupied space.

---

## Tech Stack

- **Backend:** PHP 8.5, Symfony
- **Database:** MariaDB
- **ORM:** Doctrine
- **Infrastructure:** Docker Compose, Devcontainer
- **API:** REST, versioned (`/v1/...`)

---

## Plant Data Model

### Botanical Classification
Flat fields + key references (GBIF pattern — avoids expensive JOINs):

```
kingdom, phylum, class, order, family, genus, species  # full hierarchy strings
canonical_name        # "Solanum lycopersicum"
scientific_name       # "Solanum lycopersicum L." (with authorship)
authorship            # "L."
common_names[]        # multi-language array
slug                  # "solanum-lycopersicum" (URL-safe, stable)
rank                  # species / subspecies / variety / form
taxonomic_status      # accepted / synonym / doubtful
accepted_name_id      # FK → accepted name (for synonyms)
ipni_id               # "320035-2" (International Plant Names Index)
gbif_key              # integer (GBIF backbone taxon key)
```

### Hydroponics Parameters (per growth stage)

Each parameter set is tied to a growth stage. Stages: `germinating` · `seedling` · `vegetative` · `flowering` · `fruiting` · `harvesting` · `dormant`

**Water & Nutrients:**
```
ph_min / ph_max              # e.g. 5.5 – 6.5
ec_min / ec_max              # mS/cm — e.g. 2.0 – 3.0
tds_min / tds_max            # ppm — e.g. 1000 – 1500 (1 EC ≈ 500–700 ppm)
water_temp_min / max         # °C — optimal: 18–22°C; >24°C = root rot risk
dissolved_oxygen_min         # mg/L — target >6, ideal 8–10 mg/L
n_ppm / p_ppm / k_ppm        # Nitrogen, Phosphorus, Potassium in ppm
ca_ppm / mg_ppm / s_ppm      # Calcium, Magnesium, Sulfur
```

**Environment:**
```
air_temp_min / max           # °C
humidity_min / max           # %
vpd_min / vpd_max            # kPa — Vapor Pressure Deficit
                             # seedling: 0.4–0.8 / veg: 0.8–1.2 / flower: 1.2–1.6
ppfd_min / ppfd_max          # µmol/m²/s — photosynthetic photon flux density
dli_min / dli_max            # mol/m²/day — daily light integral
photoperiod_hours            # e.g. 18 (veg) / 12 (flower)
```

**Grow System Compatibility:**
```
compatible_systems[]         # DWC / NFT / Kratky / Aeroponics / EbbFlow / Drip / Wicking
```

### Growth Characteristics
```
multi_harvest        # boolean
has_dormant          # boolean
cycle_days_min/max   # germination to harvest
yield_potential      # low / medium / high
```

### Data Quality
```
completeness_score   # 0–100 (calculated from filled fields)
ai_prefilled         # boolean — was this entry created by AI?
community_verified   # boolean — confirmed by trusted contributors
last_reviewed_at     # datetime
```

---

## API Design

### Response Envelope (consistent with Florabase)
```json
{ "status": "success|error", "data": {}, "error": null, "meta": {} }
```
Lists include `"meta": { "total": n, "per_page": 20, "current_page": 1 }`.

### Key Routes
```
GET  /v1/plants                          # paginated list, filterable
GET  /v1/plants/{id}                     # single plant
GET  /v1/plants/{id}/params              # all stage params
GET  /v1/plants/{id}/params?stage=flowering    # stage-specific params
GET  /v1/plants/{id}/params?system=dwc   # system-filtered params
GET  /v1/plants/search?q=tomato          # full-text search
GET  /v1/plants/by-slug/{slug}           # slug lookup
GET  /v1/plants/by-ipni/{ipni_id}        # IPNI lookup
GET  /v1/plants/by-gbif/{gbif_key}       # GBIF key lookup
```

### Rate Limiting Headers
Return: `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset`

---

## Community System

- Users accumulate contribution points via forum posts, data suggestions, corrections
- Above threshold → unlock direct plant data editing
- Quality grades inspired by iNaturalist:
  - `draft` — AI pre-filled, not reviewed
  - `community` — confirmed by ≥2 trusted contributors
  - `verified` — confirmed by curator/expert
- Edit history preserved for all changes (audit trail)
- Flagging system for bad data → triggers curator review

---

## Project Conventions

- Language: English (code, comments, commits, docs)
- Commits: Conventional Commits, no AI attribution
- Branch format: `{type}/{github-username}/{description}`
- Protected branch: `main` — only via PR
- Work with GitHub Milestones and Project Board
- Issues linked in commits via `Closes #X` / `Refs #X`
- Documentation in `Documentation/`
- API versioned: `/v1/...`
- Follow Symfony conventions (bundles, services, controllers, repositories)

---

## Related Repos

| Repo | Description |
|---|---|
| `tom-schorn/Florabase` | Hydroponic plant tracking backend (Flask + MySQL) |
| `tom-schorn/Florabase-Frontend` | Florabase frontend (React + Vite) |

## External References

| Resource | Use |
|---|---|
| IPNI (ipni.org) | Canonical plant name identifiers |
| GBIF (gbif.org) | Taxon keys, biodiversity cross-reference |
| WCVP | Taxonomic acceptance status |
| ICN | International Code of Nomenclature (naming rules) |

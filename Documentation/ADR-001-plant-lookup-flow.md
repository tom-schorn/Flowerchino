# ADR-001: Plant Lookup Flow (Florabase ↔ Flowerchino)

## Status
Accepted

## Context

Florabase users need to assign a plant species to their grow entries. There is no universal seed identifier — EAN barcodes are manufacturer-specific, not taxonomic. The botanical standard for species identification is the GBIF backbone taxonomy.

## Decision

### Identifier: GBIF Key

Every plant in Flowerchino is identified by its `gbif_key` (integer). This is the primary cross-reference between Florabase and Flowerchino.

### User Flow (Florabase Frontend)

1. User types a plant name into a search dropdown
2. Florabase queries the GBIF Autocomplete API live (`https://api.gbif.org/v1/species/suggest?q=...`)
3. User selects a species from the suggestions (name + gbif_key)
4. Florabase requests plant data from Flowerchino by gbif_key

### API Flow

```
GET /v1/plants/by-gbif/{gbif_key}
```

**Case A — plant exists in Flowerchino:**
```json
{
  "status": "success",
  "data": { ...full plant + stage params... },
  "meta": { "source": "existing" }
}
```

**Case B — plant does not exist yet:**
```json
{
  "status": "success",
  "data": { "id": 42, "slug": "citrullus-lanatus", "quality_grade": "pending" },
  "meta": { "source": "generating" }
}
```
→ Florabase polls `GET /v1/plants/42/status` until `quality_grade != "pending"`

**Case B trigger:** `GET /v1/plants/by-gbif/{gbif_key}` automatically queues AI pre-fill if the plant does not exist. No separate POST needed.

### Scope: Species only (MVP)

Flowerchino documents species (e.g. *Citrullus lanatus*), not cultivars ("Crimson Sweet").
Cultivars remain a free-text field in Florabase. Cultivar tracking in Flowerchino is a future consideration (V2+).

## Consequences

- `gbif_key` must be unique + indexed on the Plant entity
- `GET /v1/plants/by-gbif/{gbif_key}` must be idempotent — multiple calls never create duplicate entries
- AI pre-fill is triggered lazily on first lookup, not proactively
- Florabase does not need to implement its own plant database
- GBIF API dependency for autocomplete lives in Florabase, not Flowerchino

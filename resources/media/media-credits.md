# Media credits

Every image, video, font and audio file used in this project must be listed
here with its source, author, licence and download date (plan.md §5).

**No entries yet.** Phase 1 (Marketing Site) is where media is introduced.

---

## Rules

1. **Royalty-free sources only.** Unsplash, Pexels, Pixabay, and Wikimedia
   Commons where the licence is confirmed.
2. **Record every download** in the table below, at download time — not later.
3. **Never use copyrighted commercial advertising footage.**
4. **Never imply a stock person is a real customer, player or employee.** Do not
   name stock models as staff, use them as testimonials, or caption them as
   members of this community.
5. **Do not fabricate testimonials.** Real quotes only, once available.
6. Optimise media before shipping: WebP/AVIF where supported, responsive
   `srcset`, explicit dimensions (no layout shift), `loading="lazy"` below the
   fold, and a `poster` for video with a mobile fallback image.
7. Store media in `public/media/` and keep originals out of Git when large.

---

## Licences

| Source            | Typical licence         | Commercial use | Attribution               |
| ----------------- | ----------------------- | -------------- | ------------------------- |
| Unsplash          | Unsplash Licence        | Yes            | Not required, appreciated |
| Pexels            | Pexels Licence          | Yes            | Not required, appreciated |
| Pixabay           | Pixabay Content Licence | Yes            | Not required              |
| Wikimedia Commons | Varies per file         | Varies         | **Usually required**      |

Always record the **actual** licence shown on the download page rather than
assuming it from the table above.

---

## Images

All site photography is **CC0** (public domain dedication) from Wikimedia
Commons, cropped to the aspect ratios the design uses and re-encoded as WebP
by `storage/app/build-media.php`. CC0 was chosen deliberately: several other
Commons pickleball photos are **CC BY-SA 4.0**, and the share-alike term would
attach to these crops. CC0 keeps the project free of that obligation.

Subjects are generic courts and equipment. **No photograph shows a real
customer, employee or named member**, and none is captioned as one.

| File                   | Subject                             | Source URL                                                                                                      | Author    | Licence | Downloaded | Used on                              |
| ---------------------- | ----------------------------------- | --------------------------------------------------------------------------------------------------------------- | --------- | ------- | ---------- | ------------------------------------ |
| `courts-hall.webp`     | Indoor pickleball courts, wide hall | https://commons.wikimedia.org/wiki/File:Harry_B._Anderson_Tennis_Center_-_Pickleball_Courts_1-3.jpg             | GA Kevin  | CC0 1.0 | 2026-10-02 | Home hero, Contact page              |
| `courts-detail.webp`   | Courts 4–6, angled view             | https://commons.wikimedia.org/wiki/File:Harry_B._Anderson_Tennis_Center_-_Pickleball_Courts_4-6.jpg             | GA Kevin  | CC0 1.0 | 2026-10-02 | Home "the courts", Blog page         |
| `courts-row.webp`      | Courts 7–9, fence line              | https://commons.wikimedia.org/wiki/File:Harry_B._Anderson_Tennis_Center_-_Pickleball_Courts_7-9.jpg             | GA Kevin  | CC0 1.0 | 2026-10-02 | Courts page hero, booking CTA        |
| `courts-aerial.webp`   | Eight courts from above             | https://commons.wikimedia.org/wiki/File:Pickleball_court_in_La_Crosse,_Wisconsin_02.jpg                         | Wikideas1 | CC0 1.0 | 2026-10-02 | Home rentals split, Memberships page |
| `courts-outdoor.webp`  | Two outdoor courts, long shadow     | https://commons.wikimedia.org/wiki/File:Outdoor_pickleball_courts.jpg                                           | Wikideas1 | CC0 1.0 | 2026-10-02 | Facilities page hero, About page     |
| `courts-gear.webp`     | Paddles stacked at court side       | https://commons.wikimedia.org/wiki/File:Harry_B._Anderson_Tennis_Center_-_Pickleball_Paddle_Stacking_(East).jpg | GA Kevin  | CC0 1.0 | 2026-10-02 | Home equipment split                 |
| `courts-sky.webp`      | Courts 10–12 under a wide sky       | https://commons.wikimedia.org/wiki/File:Harry_B._Anderson_Tennis_Center_-_Pickleball_Courts_10-12.jpg           | GA Kevin  | CC0 1.0 | 2026-10-02 | Tournaments page hero                |
| `courts-stacking.webp` | Racket rack beside the fence line   | https://commons.wikimedia.org/wiki/File:Harry_B._Anderson_Tennis_Center_-_Pickleball_Paddle_Stacking_(West).jpg | GA Kevin  | CC0 1.0 | 2026-10-02 | Events page hero                     |

Derivative crops are resized and recompressed only; no other alteration was
made to the source images.

### Deliberately not used

`File:Aerial Pickleball Courts.jpg` is the most attractive photograph found — a
tropical four-court complex ringed with palms — but it is **CC BY-SA 4.0**, not
CC0. Its share-alike term would attach to these crops, so it is excluded. Two
further CC0 files were downloaded and rejected on quality rather than licence:
the _Central Pickleball Courts Entrance_ pair (dominated by a parking lot and
light poles) and _Pickleball Court Rules_ (a photograph of a rules sign, not a
court).

## Video

| File               | Subject                           | Source                                 | Author          | Licence               | Built      | Used on   |
| ------------------ | --------------------------------- | -------------------------------------- | --------------- | --------------------- | ---------- | --------- |
| `hero-loop.mp4`    | Cinematic push across four courts | Derived from the four CC0 stills above | n/a — generated | CC0 (inherits inputs) | 2026-10-02 | Home hero |
| `hero-poster.webp` | Hero poster / fallback frame      | Copy of `courts-hall.webp`             | n/a — copy      | CC0 (inherits source) | 2026-10-02 | Home hero |

**The video is generated, not downloaded.** `storage/app/build-media.php`
builds it with ffmpeg: each still becomes an 8-second shot with a slow
`zoompan` push (in and out on alternating shots), and the shots are joined by
1-second `xfade` cross-dissolves so the loop does not read as a slideshow.
Output is 1920×1080, 25 fps, H.264 (`libx264`, CRF 28), `+faststart`, 29
seconds, ~4.3 MB, silent.

**Why not downloaded footage:** the only freely-licensed pickleball videos are
U.S. Navy / Armed Forces Network clips
(`File:InFocus- Pro Pickleball Tour (956784).webm`,
`File:Pickleball Japan Federation Visits Sasebo (967848).webm`). They are
copyright-free as far as reuse goes, but both carry an "AFN" broadcast
watermark burned into the frame plus location text. Shipping a third party's
broadcast bug as brand marketing is not acceptable, and both are AV1-encoded
18–27 MB — expensive to decode for a background loop. Deriving the loop from
our own CC0 stills gives real moving footage of the right subject with no
third-party marks and no attribution obligations.

The CSS treatment (`.cinema` and `.cinema-grain` in `resources/css/app.css`)
remains in place underneath the video. To swap in real stock footage later,
source a Pexels or Pixabay clip through an API-key workflow and record it in
the table above.

## Fonts

| Family            | Source                                                                              | Licence                   | Downloaded |
| ----------------- | ----------------------------------------------------------------------------------- | ------------------------- | ---------- |
| Plus Jakarta Sans | [Google Fonts](https://fonts.google.com/specimen/Plus+Jakarta+Sans) via Bunny Fonts | SIL Open Font License 1.1 | 2026-10-02 |
| Archivo           | [Google Fonts](https://fonts.google.com/specimen/Archivo) via Bunny Fonts           | SIL Open Font License 1.1 | 2026-10-02 |

Both families are **self-hosted** through the Vite build
(`laravel-vite-plugin/fonts`), so no third-party font CDN is contacted at
runtime. Only the weights actually used are shipped — Plus Jakarta Sans
(400, 500, 600, 700, 800) and Archivo (700, 800, 900 in both normal and
italic). Archivo is the display face used by `.font-display`.

---

## Search guidance

Aim for authentic Southeast Asian / Filipino sport imagery:

- Asian pickleball players, Filipino sports players, Southeast Asian athletes
- Asian indoor sports facilities, Asian friends playing sports
- Philippine lifestyle and sports scenes
- Pickleball courts, paddles, balls and community sessions

If authentic regional imagery is unavailable, prefer generic sports imagery
that does not imply a specific nationality, and never caption a stock photo as
a real customer from a real facility.

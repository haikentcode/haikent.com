import { cp, readdir, rm } from "node:fs/promises";
import { fileURLToPath } from "node:url";
import path from "node:path";

const siteDir = path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const rootDir = path.dirname(siteDir);
const distDir = path.join(siteDir, "dist");
const scopyDir = path.join(rootDir, "scopy");

// Files/folders in the repo root that belong to the PHP backend or legacy
// assets and must never be touched by the static-site sync.
const PROTECTED = new Set([
  ".git",
  ".gitignore",
  "site",
  "contact.php",
  "search.php",
  "search_api.php",
  "pdf_text.db",
  "old_db_bkp",
  "old",
  "forms",
  "static",
  "scopy",
  "README.md",
  "Readme.txt",
  "changelog.txt",
]);

async function main() {
  const distEntries = await readdir(distDir);

  // Remove previously-synced generated files/folders at the root so renamed
  // or removed build outputs don't linger, but never touch protected paths.
  const rootEntries = await readdir(rootDir);
  for (const entry of rootEntries) {
    if (PROTECTED.has(entry)) continue;
    if (!distEntries.includes(entry)) continue;
    await rm(path.join(rootDir, entry), { recursive: true, force: true });
  }

  for (const entry of distEntries) {
    if (PROTECTED.has(entry)) {
      throw new Error(
        `Refusing to overwrite protected path "${entry}" — rename it in the Astro build output.`,
      );
    }
    await cp(path.join(distDir, entry), path.join(rootDir, entry), {
      recursive: true,
      force: true,
    });
  }

  console.log(`Synced ${distEntries.length} entries from site/dist to repo root.`);

  await syncScopySubdomain(distEntries);
}

// scopy.haikent.com is a separate Hostinger subdomain with its own document
// root (public_html/scopy/), so it needs its own copy of everything the
// search page depends on: the shared Astro build output (for the layout's
// CSS/JS, since absolute paths like /_astro/... resolve against whichever
// origin serves the page) plus the PHP backend and its data, which live
// outside the Astro build entirely.
async function syncScopySubdomain(distEntries) {
  await rm(scopyDir, { recursive: true, force: true });

  for (const entry of distEntries) {
    if (entry === "search.html" || entry === "index.html") continue;
    await cp(path.join(distDir, entry), path.join(scopyDir, entry), {
      recursive: true,
      force: true,
    });
  }
  // The subdomain's root page *is* the search tool.
  await cp(path.join(distDir, "search.html"), path.join(scopyDir, "index.html"));

  for (const entry of ["search_api.php", "pdf_text.db", "static"]) {
    await cp(path.join(rootDir, entry), path.join(scopyDir, entry), {
      recursive: true,
      force: true,
    });
  }

  console.log("Synced search tool + backend into scopy/ for scopy.haikent.com.");
}

main().catch((error) => {
  console.error(error);
  process.exit(1);
});

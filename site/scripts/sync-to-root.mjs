import { cp, readdir, rm } from "node:fs/promises";
import { fileURLToPath } from "node:url";
import path from "node:path";

const siteDir = path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const rootDir = path.dirname(siteDir);
const distDir = path.join(siteDir, "dist");

// Files/folders in the repo root that belong to the PHP backend or legacy
// assets and must never be touched by the static-site sync.
const PROTECTED = new Set([
  ".git",
  ".gitignore",
  "site",
  "contact.php",
  "search.php",
  "search_api.php",
  "scopy.php",
  "scopy",
  "pdf_text.db",
  "old_db_bkp",
  "old",
  "forms",
  "static",
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
}

main().catch((error) => {
  console.error(error);
  process.exit(1);
});

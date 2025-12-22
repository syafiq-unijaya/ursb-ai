import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';
import { execSync } from 'child_process';
import fs from 'fs';
import path from 'path';

function findPhpExecutable(): string | null {
    // Try explicit environment overrides first (don't require an exists check — we will validate by running)
    const envCandidates = [process.env.PHP_BIN, process.env.PHP, process.env.PHP_PATH, process.env.PHP_EXECUTABLE];
    for (const cand of envCandidates) {
        if (!cand) continue;
        try {
            execSync(`"${cand}" -v`);
            return cand;
        } catch {
            // not usable
        }
    }

    // Try VS Code workspace setting if present
    try {
        const settingsPath = path.resolve(__dirname, '.vscode', 'settings.json');
        if (fs.existsSync(settingsPath)) {
            const raw = fs.readFileSync(settingsPath, 'utf8');
            // strip JS-style comments so JSON.parse doesn't fail on files with comments
            let cleaned = raw.replace(/\/\*[\s\S]*?\*\//g, '').replace(/\/\/.*$/gm, '');
            // remove trailing commas to be tolerant of VS Code style settings
            cleaned = cleaned.replace(/,\s*(?=[}\]])/g, '');
            const json = JSON.parse(cleaned);
            const candidate = json['php.executablePath'] || json['php.validate.executablePath'];
            if (candidate) {
                try {
                    execSync(`"${candidate}" -v`);
                    return candidate;
                } catch {
                    // not usable
                }
            }
        }
    } catch (e) {
        // ignore
    }

    // Fallback to system php in PATH (which / where)
    try {
        const whichOut = execSync('which php').toString().trim();
        if (whichOut) {
            try {
                execSync(`"${whichOut}" -v`);
                return whichOut;
            } catch {
                // ignore
            }
        }
    } catch {
        try {
            const whereOut = execSync('where php').toString().split(/\r?\n/).find(Boolean)?.trim();
            if (whereOut) {
                try {
                    execSync(`"${whereOut}" -v`);
                    return whereOut;
                } catch {
                    // ignore
                }
            }
        } catch {
            // ignore
        }
    }

    return null;
}

function phpSupportsWayfinder(): boolean {
    const phpPath = findPhpExecutable();
    if (!phpPath) return false;

    try {
        const out = execSync(`"${phpPath}" -v`).toString();
        const match = out.match(/PHP\s+(\d+)\.(\d+)/);
        if (!match) return false;
        const major = Number(match[1]);
        const minor = Number(match[2]);

        // eslint-disable-next-line no-console
        console.info(`Using PHP executable: ${phpPath} (version ${major}.${minor}) for Wayfinder check`);

        return major > 8 || (major === 8 && minor >= 4);
    } catch (e) {
        return false;
    }
}

const plugins = [
    laravel({
        input: ['resources/js/app.ts'],
        ssr: 'resources/js/ssr.ts',
        refresh: true,
    }),
    tailwindcss(),
    vue({
        template: {
            transformAssetUrls: {
                base: null,
                includeAbsolute: false,
            },
        },
    }),
];

// Decide whether to enable Wayfinder. We prefer using an explicit PHP executable if available.
const phpPath = findPhpExecutable();
if (phpPath) {
    try {
        const out = execSync(`"${phpPath}" -v`).toString();
        const match = out.match(/PHP\s+(\d+)\.(\d+)/);
        const major = match ? Number(match[1]) : 0;
        const minor = match ? Number(match[2]) : 0;

        if (major > 8 || (major === 8 && minor >= 4)) {
            // Prepend the php executable's directory to PATH so child processes invoked as `php` use this binary.
            const phpDir = path.dirname(phpPath);
            process.env.PATH = `${phpDir}${path.delimiter}${process.env.PATH}`;

            // eslint-disable-next-line no-console
            console.info(`Enabling Wayfinder plugin using PHP at: ${phpPath}`);
            plugins.splice(2, 0, wayfinder({ formVariants: true }));
        } else {
            // eslint-disable-next-line no-console
            console.warn(`Skipping Wayfinder plugin: detected PHP ${major}.${minor}, but >= 8.4 required.`);
        }
    } catch (e) {
        // eslint-disable-next-line no-console
        console.warn('Skipping Wayfinder plugin: could not execute selected PHP binary.');
    }
} else {
    // eslint-disable-next-line no-console
    console.warn('Skipping Wayfinder plugin: PHP 8.4+ required for generation.');
}

export default defineConfig({
    plugins,
});

import fs from 'fs';
import path from 'path';

const files = [
    'resources/js/Pages/Home.vue',
    'resources/js/Pages/Auth/Login.vue',
    'resources/js/Pages/Auth/Register.vue',
    'resources/js/Pages/Complex/Show.vue',
    'resources/js/Pages/Client/Dashboard.vue',
    'resources/js/Pages/AdminCancha/Dashboard.vue',
    'resources/js/Pages/SuperAdmin/Dashboard.vue'
];

const replacements = {
    // Backgrounds
    'bg-slate-900/60': 'bg-white/60 dark:bg-slate-900/60',
    'bg-slate-950': 'bg-slate-50 dark:bg-slate-950',
    'bg-slate-950/70': 'bg-slate-50/70 dark:bg-slate-950/70',
    'bg-slate-900': 'bg-white dark:bg-slate-900',
    'bg-slate-800': 'bg-slate-100 dark:bg-slate-800',
    // Borders
    'border-slate-800': 'border-slate-200 dark:border-slate-800',
    'border-slate-700': 'border-slate-300 dark:border-slate-700',
    'border-slate-600': 'border-slate-400 dark:border-slate-600',
    // Text
    'text-slate-50': 'text-slate-900 dark:text-slate-50',
    'text-slate-100': 'text-slate-900 dark:text-slate-100',
    'text-slate-200': 'text-slate-800 dark:text-slate-200',
    'text-slate-300': 'text-slate-600 dark:text-slate-300',
    'text-slate-400': 'text-slate-500 dark:text-slate-400',
    'text-emerald-300': 'text-emerald-600 dark:text-emerald-300',
    'text-emerald-400': 'text-emerald-600 dark:text-emerald-400',
    'text-amber-300': 'text-amber-600 dark:text-amber-300',
    // Hover
    'hover:bg-slate-900': 'hover:bg-slate-100 dark:hover:bg-slate-900',
    'hover:bg-slate-800': 'hover:bg-slate-200 dark:hover:bg-slate-800',
    'hover:text-emerald-200': 'hover:text-emerald-700 dark:hover:text-emerald-200',
    // Special
    'bg-emerald-400/10': 'bg-emerald-100 dark:bg-emerald-400/10',
    'bg-emerald-400/20': 'bg-emerald-200 dark:bg-emerald-400/20',
    'hover:bg-emerald-400/30': 'hover:bg-emerald-300 dark:hover:bg-emerald-400/30',
    'border-emerald-300/30': 'border-emerald-500/30 dark:border-emerald-300/30',
};

files.forEach(file => {
    const filePath = path.join('c:\\laragon\\www\\CanchaAlquiler', file);
    if (!fs.existsSync(filePath)) {
        console.log(`File not found: ${filePath}`);
        return;
    }
    
    let content = fs.readFileSync(filePath, 'utf8');
    
    let modified = false;
    for (const [key, value] of Object.entries(replacements)) {
        const regex = new RegExp(`(?<=["'\\s])${key.replace(/[.*+?^${}()|[\\]\\\\]/g, '\\$&')}(?=["'\\s])`, 'g');
        if (regex.test(content)) {
            content = content.replace(regex, value + ' transition-colors duration-300');
            modified = true;
        }
    }
    
    // Fallback: also replace without exact word boundaries for some cases, but word boundary is safer.
    // Given Vue's template strings, `class="..."` always bounds by quotes or spaces.
    
    if (modified) {
        fs.writeFileSync(filePath, content, 'utf8');
        console.log(`Updated ${file}`);
    } else {
        console.log(`No changes needed for ${file}`);
    }
});

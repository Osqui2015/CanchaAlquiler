import fs from "fs";
const path =
    "c:\\laragon\\www\\CanchaAlquiler\\resources\\js\\Pages\\AdminCancha\\Dashboard.vue";
const s = fs.readFileSync(path, "utf8");
const lines = s.split(/\r?\n/);
for (let i = 0; i < lines.length; i++) {
    if (
        lines[i].includes("rankid") ||
        lines[i].includes("podiums_third") ||
        lines[i].includes("member_names: null")
    ) {
        console.log(i + 1, lines[i]);
        const start = Math.max(0, i - 6);
        const end = Math.min(lines.length - 1, i + 6);
        for (let j = start; j <= end; j++) {
            console.log((j + 1).toString().padStart(4, " "), lines[j]);
        }
        console.log("---");
    }
}

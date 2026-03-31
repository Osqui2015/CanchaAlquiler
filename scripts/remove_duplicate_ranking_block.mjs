import fs from 'fs';
const path = 'c:/laragon/www/CanchaAlquiler/resources/js/Pages/AdminCancha/Dashboard.vue';
let s = fs.readFileSync(path,'utf8');
const startToken = "type: 'individual',";
const funcToken = 'function removeRankingEntry(';
const startIdx = s.indexOf(startToken);
if(startIdx===-1){console.log('startToken not found'); process.exit(0)}
const funcIdx = s.indexOf(funcToken, startIdx);
if(funcIdx===-1){console.log('function token not found after start'); process.exit(0)}
// remove from startIdx up to funcIdx (but keep function declaration)
const before = s.slice(0,startIdx);
const after = s.slice(funcIdx);
fs.writeFileSync(path, before+after, 'utf8');
console.log('removed block from', startIdx, 'to', funcIdx);

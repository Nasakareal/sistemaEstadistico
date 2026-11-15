import fs from "node:fs/promises";
import { spawn } from "node:child_process";
import { FileBlob, SpreadsheetFile } from "@oai/artifact-tool";

const sourcePath = "C:/Users/naasa/OneDrive/Escritorio/vehiculos_conduce_con_legalidad_todos.xlsx";
const queryScriptPath = "C:/wamp64/www/sistemaEstadistico/tmp/update_conduce_excel/export_new_rows.php";
const outputDir = "C:/wamp64/www/sistemaEstadistico/outputs/01a1cdfb-d731-7e31-9dc6-b7efe630deed";
const inspectionDir = "C:/wamp64/www/sistemaEstadistico/tmp/update_conduce_excel/final_inspection";
const outputPath = `${outputDir}/vehiculos_conduce_con_legalidad_todos_actualizado_2026-11-13.xlsx`;

function runSshPhp(script) {
  return new Promise((resolve, reject) => {
    const child = spawn("ssh", [
      "-o", "BatchMode=yes",
      "nasaka@158.23.170.129",
      "cd /var/www/html/sistemaEstadistico && php",
    ], { stdio: ["pipe", "pipe", "pipe"] });
    const stdout = [];
    const stderr = [];
    child.stdout.on("data", (chunk) => stdout.push(chunk));
    child.stderr.on("data", (chunk) => stderr.push(chunk));
    child.on("error", reject);
    child.on("close", (code) => {
      const out = Buffer.concat(stdout).toString("utf8");
      const err = Buffer.concat(stderr).toString("utf8");
      if (code !== 0) {
        reject(new Error(`La consulta SSH terminó con código ${code}: ${err}`));
        return;
      }
      resolve(out);
    });
    child.stdin.end(script);
  });
}

function clean(value) {
  if (value === null || value === undefined) return "";
  return String(value).trim();
}

function joinPresent(parts) {
  return parts.filter((value) => clean(value) !== "").join(", ");
}

function buildLocation(row) {
  return joinPresent([
    row.operativo_lugar,
    clean(row.operativo_numero) ? `No. ${clean(row.operativo_numero)}` : "",
    clean(row.operativo_colonia) ? `Col. ${clean(row.operativo_colonia)}` : "",
    row.operativo_municipio,
  ]);
}

function buildVehicle(row) {
  const fields = [
    ["Marca", row.marca],
    ["Línea", row.linea],
    ["Modelo", row.modelo],
    ["Tipo", row.tipo],
    ["Color", row.color],
    ["Placas", row.placas],
    ["Estado placas", row.estado_placas],
    ["Serie", row.serie],
    ["No. inventario", row.numero_inventario],
    ["Servicio", row.tipo_servicio],
  ];
  return fields
    .filter(([, value]) => clean(value) !== "")
    .map(([label, value]) => `${label}: ${clean(value)}`)
    .join("; ");
}

function excelSerial(dateText, timeText) {
  const [year, month, day] = clean(dateText).split("-").map(Number);
  const [hour = 0, minute = 0, second = 0] = clean(timeText || "00:00:00").split(":").map(Number);
  return Date.UTC(year, month - 1, day, hour, minute, second) / 86400000 + 25569;
}

await fs.mkdir(outputDir, { recursive: true });
await fs.mkdir(inspectionDir, { recursive: true });

const phpScript = await fs.readFile(queryScriptPath, "utf8");
const payload = JSON.parse(await runSshPhp(phpScript));

if (payload.total !== 208) {
  throw new Error(`Se esperaban 208 registros nuevos y producción devolvió ${payload.total}. Se detuvo para no generar un archivo incompleto.`);
}

const expectedByDate = { "2026-11-10": 95, "2026-11-11": 98, "2026-11-13": 15 };
for (const [date, expected] of Object.entries(expectedByDate)) {
  if (Number(payload.by_date?.[date] ?? 0) !== expected) {
    throw new Error(`Conteo inesperado para ${date}: ${payload.by_date?.[date] ?? 0}; esperado ${expected}.`);
  }
}

const workbook = await SpreadsheetFile.importXlsx(await FileBlob.load(sourcePath));
const sheet = workbook.worksheets.getItem("Vehículos");
const table = sheet.tables.getItem("VehiculosConduceLegalidad");

const existingRange = table.getRange();
if (existingRange.address !== "A5:D439") {
  throw new Error(`La tabla fuente cambió: ${existingRange.address}; se esperaba A5:D439.`);
}
if (Number(sheet.getRange("B3").values?.[0]?.[0]) !== 434) {
  throw new Error(`El total fuente cambió; se esperaba 434.`);
}

const newRows = payload.rows.map((row) => [
  excelSerial(row.operativo_fecha, row.operativo_hora_inicio),
  buildLocation(row),
  buildVehicle(row),
  clean(row.motivo_retencion),
]);

table.rows.add(null, newRows);
sheet.getRange("B3").values = [[434 + newRows.length]];

const addedRange = sheet.getRange(`A440:D${439 + newRows.length}`);
addedRange.format.wrapText = true;
addedRange.format.verticalAlignment = "top";
sheet.getRange(`A440:A${439 + newRows.length}`).format.numberFormat = "m/d/yy";
sheet.getRange(`A440:A${439 + newRows.length}`).format.horizontalAlignment = "center";
addedRange.format.autofitRows();
workbook.recalculate();

const finalTableRange = table.getRange().address;
if (finalTableRange !== "A5:D647") {
  throw new Error(`La tabla final quedó en ${finalTableRange}; se esperaba A5:D647.`);
}
if (Number(sheet.getRange("B3").values?.[0]?.[0]) !== 642) {
  throw new Error("El total final no quedó en 642.");
}

const boundary = await workbook.inspect({
  kind: "table",
  sheetId: "Vehículos",
  range: "A434:D446",
  include: "values,formulas",
  tableMaxRows: 20,
  tableMaxCols: 4,
  maxChars: 18000,
});
const tail = await workbook.inspect({
  kind: "table",
  sheetId: "Vehículos",
  range: "A637:D647",
  include: "values,formulas",
  tableMaxRows: 15,
  tableMaxCols: 4,
  maxChars: 18000,
});
const errors = await workbook.inspect({
  kind: "match",
  searchTerm: "#REF!|#DIV/0!|#VALUE!|#NAME\\?|#N/A",
  options: { useRegex: true, maxResults: 100 },
  summary: "final formula error scan",
});
if (errors.ndjson && !errors.ndjson.includes('"matches":[]') && /#REF!|#DIV\/0!|#VALUE!|#NAME\?|#N\/A/.test(errors.ndjson)) {
  throw new Error(`Se detectaron errores en la hoja: ${errors.ndjson}`);
}

const boundaryPng = await workbook.render({ sheetName: "Vehículos", range: "A434:D446", scale: 1.4, format: "png" });
await fs.writeFile(`${inspectionDir}/boundary.png`, new Uint8Array(await boundaryPng.arrayBuffer()));
const tailPng = await workbook.render({ sheetName: "Vehículos", range: "A637:D647", scale: 1.4, format: "png" });
await fs.writeFile(`${inspectionDir}/tail.png`, new Uint8Array(await tailPng.arrayBuffer()));

const output = await SpreadsheetFile.exportXlsx(workbook);
await output.save(outputPath);

console.log(JSON.stringify({
  outputPath,
  sourceTotal: 434,
  added: newRows.length,
  finalTotal: 642,
  byDate: payload.by_date,
  byOperation: payload.by_operation,
  finalTableRange,
  boundary: boundary.ndjson,
  tail: tail.ndjson,
  errorScan: errors.ndjson,
}, null, 2));

import fs from "node:fs/promises";
import { FileBlob, SpreadsheetFile } from "@oai/artifact-tool";

const source = "C:/Users/naasa/OneDrive/Escritorio/vehiculos_conduce_con_legalidad_todos.xlsx";
const outDir = "C:/wamp64/www/sistemaEstadistico/tmp/update_conduce_excel/inspection";
await fs.mkdir(outDir, { recursive: true });

const workbook = await SpreadsheetFile.importXlsx(await FileBlob.load(source));
const summary = await workbook.inspect({
  kind: "workbook,sheet,table,definedName,drawing",
  maxChars: 12000,
  tableMaxRows: 12,
  tableMaxCols: 20,
  tableMaxCellChars: 120,
});
console.log(summary.ndjson);

for (let i = 0; i < workbook.worksheets.items.length; i++) {
  const sheet = workbook.worksheets.getItemAt(i);
  const used = sheet.getUsedRange();
  console.log(JSON.stringify({
    index: i,
    name: sheet.name,
    usedAddress: used?.address ?? null,
    rows: used?.rowCount ?? null,
    columns: used?.columnCount ?? null,
    tables: sheet.tables.items.map(t => ({ name: t.name, range: t.getRange().address })),
  }));
  if (used) {
    const topRows = Math.min(15, used.rowCount);
    const topCols = Math.min(25, used.columnCount);
    const region = await workbook.inspect({
      kind: "table",
      sheetId: sheet.name,
      range: sheet.getRangeByIndexes(0, 0, topRows, topCols).address,
      include: "values,formulas",
      tableMaxRows: topRows,
      tableMaxCols: topCols,
      maxChars: 15000,
    });
    console.log(region.ndjson);

    const tailStart = Math.max(0, used.rowCount - 15);
    const tail = await workbook.inspect({
      kind: "table",
      sheetId: sheet.name,
      range: sheet.getRangeByIndexes(tailStart, 0, used.rowCount - tailStart, topCols).address,
      include: "values,formulas",
      tableMaxRows: 20,
      tableMaxCols: topCols,
      maxChars: 15000,
    });
    console.log(tail.ndjson);

    const ranges = ["A1:D20", sheet.getRangeByIndexes(Math.max(0, used.rowCount - 20), 0, Math.min(20, used.rowCount), Math.min(4, used.columnCount)).address];
    for (let j = 0; j < ranges.length; j++) {
      const preview = await workbook.render({ sheetName: sheet.name, range: ranges[j], scale: 1.5, format: "png" });
      await fs.writeFile(`${outDir}/sheet_${String(i + 1).padStart(2, "0")}_${j === 0 ? "top" : "tail"}.png`, new Uint8Array(await preview.arrayBuffer()));
    }
  }
}

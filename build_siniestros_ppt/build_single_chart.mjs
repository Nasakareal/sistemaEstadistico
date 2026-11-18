import fs from "node:fs/promises";
import path from "node:path";
import { pathToFileURL } from "node:url";

const runtimeModules = "C:/Program Files/WindowsApps/OpenAI.CodexPrimaryRuntime.v26-1116-1220-0_26.1116.1220.0_x64__3k8sg7r9htsxt/dependencies/node/node_modules";
const runtimePython = "C:/Program Files/WindowsApps/OpenAI.CodexPrimaryRuntime.v26-1116-1220-0_26.1116.1220.0_x64__3k8sg7r9htsxt/dependencies/python/python.exe";
const skillDir = "C:/Users/naasa/.codex/plugins/cache/openai-primary-runtime/presentations/26.1116.12020/skills/presentations";
const workspaceDir = "C:/wamp64/www/sistemaEstadistico";
const buildDir = path.join(workspaceDir, "build_siniestros_ppt", "single_chart");
const finalPptx = path.join(workspaceDir, "output", "Grafica_Total_Hechos_Corte_Dia_17_Cada_Mes_2026.pptx");
const finalPng = path.join(workspaceDir, "output", "Grafica_Total_Hechos_Corte_Dia_17_Cada_Mes_2026.png");
process.env.RUNTIME_NODE_MODULES = runtimeModules;

const { Presentation, PresentationFile } = await import(pathToFileURL(path.join(runtimeModules, "@oai/artifact-tool/dist/artifact_tool.mjs")).href);
const { finalizePresentation, applyPresentationChartFont } = await import(pathToFileURL(path.join(skillDir, "container_tools/artifact_tool_utils.mjs")).href);

const months = ["Ene", "Feb", "Mar", "Abr", "May", "Jun", "Jul", "Ago", "Sep", "Oct", "Nov"];
const totals = [163, 191, 212, 165, 287, 345, 340, 290, 350, 353, 346];
const font = "Arial";
const colors = { navy: "#102A43", blue: "#2B6AA8", amber: "#E9A437", green: "#2F855A", gray: "#637381", grid: "#DDE4EA", white: "#FFFFFF" };

const deck = Presentation.create({ slideSize: { width: 1280, height: 720 } });
deck.theme.defaultFont = font;
const slide = deck.slides.add();
slide.background.fill = colors.white;

function addText(value, left, top, width, height, size, color, bold = false, align = "left") {
  const shape = slide.shapes.add({ geometry: "textbox", position: { left, top, width, height }, fill: "none", line: { style: "solid", fill: "none", width: 0 } });
  shape.text = value;
  shape.text.style = { typeface: font, fontSize: `${size}pt`, color, bold, alignment: align };
  return shape;
}

slide.shapes.add({ geometry: "rect", position: { left: 0, top: 0, width: 18, height: 720 }, fill: colors.amber, line: { style: "solid", fill: colors.amber, width: 0 } });
addText("Hechos de tránsito del día 1 al 17 de cada mes", 66, 38, 1050, 55, 30, colors.navy, true);
addText("Total combinado de las unidades 1 y 2 · Producción 2026", 68, 96, 1000, 30, 16, colors.gray);

const chart = slide.charts.add("bar", {
  position: { left: 75, top: 150, width: 1130, height: 455 },
  categories: months,
  series: [{ name: "Total de hechos", values: totals, fill: colors.blue }],
  barOptions: { direction: "column", grouping: "clustered", gapWidth: 48 },
  hasLegend: false,
  dataLabels: { showValue: true, position: "outEnd", textStyle: { typeface: font, fontSize: 13, fill: colors.navy, bold: true } },
  xAxis: { textStyle: { typeface: font, fontSize: 12, fill: colors.gray }, line: { style: "solid", fill: colors.grid, width: 1 } },
  yAxis: { min: 0, max: 400, majorUnit: 50, title: { text: "Hechos", textStyle: { typeface: font, fontSize: 12, fill: colors.gray } }, textStyle: { typeface: font, fontSize: 11, fill: colors.gray }, majorGridlines: { style: "solid", fill: colors.grid, width: 1 }, line: { style: "solid", fill: colors.grid, width: 1 } },
  chartFill: "none",
  plotAreaFill: "none",
  chartLine: { fill: "none", width: 0 },
});
applyPresentationChartFont(chart, { fontFamily: font });

addText("Corte homogéneo: días 1 al 17 de cada mes", 78, 650, 520, 24, 12, colors.gray);
addText("Fuente: base de datos de producción · consulta de solo lectura", 690, 650, 500, 24, 11, colors.gray, false, "right");
slide.speakerNotes.text = "Consulta de producción ejecutada por SSH en modo de solo lectura. Filtro: unidad_org_id IN (1,2), año 2026 y día del mes menor o igual a 17. Cada barra usa exactamente el mismo corte temporal: días 1 al 17.";

await fs.mkdir(buildDir, { recursive: true });
await fs.mkdir(path.dirname(finalPptx), { recursive: true });
const png = await deck.export({ slide, format: "png", scale: 1 });
await fs.writeFile(finalPng, new Uint8Array(await png.arrayBuffer()));

const candidatePath = path.join(buildDir, "candidate.pptx");
await (await PresentationFile.exportPptx(deck)).save(candidatePath);
const result = await finalizePresentation({
  explicitTotalSlideCount: 1,
  requiredNativeTableOwnerSlides: [],
  requiredNativeChartOwnerSlides: [1],
  materializeLiteralChartWorkbooks: true,
  nativeChartTargetApplication: "powerpoint",
  workspaceDir,
  candidatePath,
  finalPath: finalPptx,
  pythonExecutable: runtimePython,
  integrityValidatorPath: path.join(skillDir, "container_tools/inspect_presentation_package_integrity.py"),
  layoutValidatorPath: path.join(skillDir, "container_tools/inspect_presentation_layout_geometry.py"),
  layoutArgs: ["--expected-slide-size-emu", "12192000,6858000", "--validate-heading-fit"],
  fontPolicy: { basis: "design", families: [font] },
  verifyArtifactToolImport: true,
  receiptPath: path.join(buildDir, "validation-corte-dia-17.json"),
});

console.log(JSON.stringify({ finalPptx, finalPng, validation: result.finalSha256 }, null, 2));

import fs from "node:fs/promises";
import path from "node:path";
import { pathToFileURL } from "node:url";

const RUNTIME_NODE_MODULES = "C:/Program Files/WindowsApps/OpenAI.CodexPrimaryRuntime.v26-1116-1220-0_26.1116.1220.0_x64__3k8sg7r9htsxt/dependencies/node/node_modules";
const RUNTIME_PYTHON = "C:/Program Files/WindowsApps/OpenAI.CodexPrimaryRuntime.v26-1116-1220-0_26.1116.1220.0_x64__3k8sg7r9htsxt/dependencies/python/python.exe";
const SKILL_DIR = "C:/Users/naasa/.codex/plugins/cache/openai-primary-runtime/presentations/26.1116.12020/skills/presentations";
const workspaceDir = "C:/wamp64/www/sistemaEstadistico";
const buildDir = path.join(workspaceDir, "build_siniestros_ppt");
const previewDir = path.join(buildDir, "previews");
const stagingDir = path.join(buildDir, "finalizer");
const finalPath = path.join(workspaceDir, "output", "Analisis_Hechos_Siniestros_Delegaciones_Produccion_2026_FINAL_v2.pptx");

process.env.RUNTIME_NODE_MODULES = RUNTIME_NODE_MODULES;

const artifactModule = await import(pathToFileURL(path.join(RUNTIME_NODE_MODULES, "@oai/artifact-tool/dist/artifact_tool.mjs")).href);
const { Presentation, PresentationFile } = artifactModule;
const { finalizePresentation, applyPresentationChartFont } = await import(pathToFileURL(path.join(SKILL_DIR, "container_tools/artifact_tool_utils.mjs")).href);

const W = 1280;
const H = 720;
const FONT = "Arial";
const C = {
  navy: "#102A43",
  blue: "#2563A6",
  lightBlue: "#79A9D1",
  amber: "#E6A23C",
  red: "#C94C4C",
  green: "#2F855A",
  ink: "#1B2633",
  gray: "#637381",
  grid: "#DDE4EA",
  pale: "#F4F7FA",
  white: "#FFFFFF",
};

const months = ["Ene", "Feb", "Mar", "Abr", "May", "Jun", "Jul", "Ago", "Sep", "Oct"];
const hechos2025 = [283, 329, 342, 292, 379, 361, 292, 341, 366, 345];
const hechos2026 = [329, 329, 366, 318, 352, 359, 365, 340, 412, 415];
const delegaciones2026 = [0, 0, 0, 0, 218, 243, 214, 218, 239, 250];
const hechosCombinados2026 = hechos2026.map((v, i) => v + delegaciones2026[i]);
const diasMes = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31];
const diarios2026 = hechosCombinados2026.map((v, i) => Number((v / diasMes[i]).toFixed(2)));
const resueltos2026 = [249, 271, 302, 262, 280, 299, 304, 265, 342, 341];
const tasaRes2026 = resueltos2026.map((v, i) => Number((v / hechos2026[i]).toFixed(4)));
const resueltosDelegaciones2026 = [null, null, null, null, 140, 143, 133, 141, 148, 169];
const tasaResDelegaciones2026 = resueltosDelegaciones2026.map((v, i) => v === null ? null : Number((v / delegaciones2026[i]).toFixed(4)));
const actividades = [187, 120, 210, 168, 202, 485, 438, 430, 420];
const actividadesDelegaciones = [0, 0, 46, 6686, 6809, 7943, 10401, 12417, 13357];
const actividadMonths = ["Feb", "Mar", "Abr", "May", "Jun", "Jul", "Ago", "Sep", "Oct"];
const lesionados = [22, 23, 30, 23, 119, 125, 93, 93, 92, 138];
const hospitalizados = [22, 20, 18, 15, 67, 86, 50, 55, 60, 84];
const fallecidos = [0, 1, 2, 2, 14, 6, 8, 4, 4, 7];
const weekValues = [63,78,83,87,73,80,103,83,87,76,89,77,58,58,82,84,83,77,70,83,92,86,79,79,96,85,85,91,72,69,72,74,80,85,87,105,93,105,93,87,98,93,92];
const weekCats = weekValues.map((_, i) => `S${String(i + 2).padStart(2, "0")}`);

const presentation = Presentation.create({ slideSize: { width: W, height: H } });
presentation.theme.defaultFont = FONT;
presentation.theme.colorScheme = {
  name: "Seguridad vial",
  themeColors: {
    dk1: C.ink, lt1: C.white, dk2: C.navy, lt2: C.pale,
    accent1: C.blue, accent2: C.amber, accent3: C.green,
    accent4: C.lightBlue, accent5: C.red, accent6: C.gray,
    hlink: C.blue, folHlink: C.navy,
  },
};

function text(slide, value, x, y, w, h, size = 20, color = C.ink, bold = false, align = "left") {
  const shape = slide.shapes.add({
    geometry: "textbox",
    position: { left: x, top: y, width: w, height: h },
    fill: "none",
    line: { style: "solid", fill: "none", width: 0 },
  });
  shape.text = value;
  shape.text.style = { fontSize: `${size}pt`, typeface: FONT, color, bold, alignment: align };
  return shape;
}

function rect(slide, x, y, w, h, fill, line = fill) {
  return slide.shapes.add({
    geometry: "rect",
    position: { left: x, top: y, width: w, height: h },
    fill,
    line: { style: "solid", fill: line, width: line === "none" ? 0 : 1 },
  });
}

function baseSlide(title, subtitle = "") {
  const slide = presentation.slides.add();
  slide.background.fill = C.white;
  rect(slide, 0, 0, 18, H, C.amber);
  text(slide, title, 58, 36, 1130, 54, 30, C.navy, true);
  if (subtitle) text(slide, subtitle, 60, 94, 1120, 32, 15, C.gray, false);
  rect(slide, 60, 130, 1160, 2, C.grid);
  return slide;
}

function footer(slide, page, note = "Fuente: producción · hechos de Siniestros y Delegaciones · consulta de solo lectura") {
  text(slide, note, 60, 681, 1040, 22, 10, C.gray);
  text(slide, String(page).padStart(2, "0"), 1130, 679, 80, 22, 10, C.gray, true, "right");
}

function styleChart(chart) {
  applyPresentationChartFont(chart, { fontFamily: FONT });
  return chart;
}

function chartAxes(yTitle = undefined, max = undefined, numberFormatCode = "0") {
  return {
    xAxis: { textStyle: { typeface: FONT, fontSize: 12, fill: C.gray }, line: { style: "solid", fill: C.grid, width: 1 } },
    yAxis: {
      title: yTitle ? { text: yTitle, textStyle: { typeface: FONT, fontSize: 12, fill: C.gray } } : undefined,
      min: 0,
      max,
      numberFormatCode,
      textStyle: { typeface: FONT, fontSize: 12, fill: C.gray },
      majorGridlines: { style: "solid", fill: C.grid, width: 1 },
      line: { style: "solid", fill: C.grid, width: 1 },
    },
  };
}

// 1. Cover
{
  const s = presentation.slides.add();
  s.background.fill = C.navy;
  rect(s, 72, 92, 8, 486, C.amber);
  text(s, "ANÁLISIS EJECUTIVO", 112, 96, 520, 32, 14, C.lightBlue, true);
  text(s, "Hechos de tránsito\ny capacidad operativa", 108, 150, 760, 190, 42, C.white, true);
  text(s, "Unidades de Siniestros y Delegaciones · datos de producción", 112, 370, 760, 74, 21, "#D9E6F2");
  text(s, "ENERO–OCTUBRE 2026", 112, 520, 420, 34, 16, C.amber, true);
  text(s, "Meses completos de enero a octubre · consulta al 17 de noviembre", 112, 565, 620, 28, 12, "#B8C7D6");
  text(s, "2026", 900, 170, 280, 180, 72, "#31506B", true, "right");
  s.speakerNotes.text = "Fuente: base de datos de producción consultada por SSH en modo de solo lectura. Universo: hechos con unidad_org_id 1 y 2. El análisis principal usa meses completos de enero a octubre de 2026.";
}

// 2. Executive summary
{
  const s = baseSlide("Cobertura conjunta de Siniestros y Delegaciones", "Enero–octubre de 2026 · unidades 1 y 2");
  const metrics = [
    ["4,967", "hechos registrados", "3,585 Siniestros · 1,382 Delegaciones", C.blue],
    ["76.3%", "de asuntos resueltos", "81.4% U1 · 63.2% U2", C.green],
    ["31", "asuntos pendientes", "todos corresponden a la unidad 1", C.amber],
  ];
  metrics.forEach((m, i) => {
    const x = 70 + i * 385;
    text(s, m[0], x, 164, 320, 78, 42, m[3], true);
    text(s, m[1], x, 244, 320, 32, 17, C.ink, true);
    text(s, m[2], x, 280, 330, 32, 13, C.gray);
  });
  const ch = s.charts.add("line", {
    position: { left: 90, top: 350, width: 1080, height: 270 },
    categories: months,
    series: [{ name: "Total U1 + U2", values: hechosCombinados2026, line: { style: "solid", fill: C.blue, width: 4 }, marker: { symbol: "circle", size: 7, fill: C.blue } }],
    hasLegend: false,
    ...chartAxes("Hechos", 720),
    dataLabels: { showValue: true, position: "top", textStyle: { typeface: FONT, fontSize: 11, fill: C.navy, bold: true } },
    chartFill: "none", plotAreaFill: "none", chartLine: { fill: "none", width: 0 },
  });
  styleChart(ch);
  footer(s, 2);
  s.speakerNotes.text = "Producción, enero–octubre 2026. Unidad 1: 3,585 hechos, 2,917 resueltos y 31 pendientes. Unidad 2: 1,382 hechos, 874 resueltos y 0 pendientes. El estado de 102 registros no coincide con las tres categorías principales o permanece fuera de ellas.";
}

// 3. YoY monthly
{
  const s = baseSlide("Delegaciones amplió la cobertura desde mayo", "Hechos mensuales por unidad durante 2026");
  const ch = s.charts.add("bar", {
    position: { left: 82, top: 160, width: 1110, height: 420 },
    categories: months,
    series: [
      { name: "Unidad 1 · Siniestros", values: hechos2026, fill: C.blue, dataLabels: { showValue: true, position: "center", textStyle: { typeface: FONT, fontSize: 10, fill: C.white, bold: true } } },
      { name: "Unidad 2 · Delegaciones", values: delegaciones2026, fill: C.amber, dataLabels: { showValue: true, position: "center", textStyle: { typeface: FONT, fontSize: 10, fill: C.white, bold: true } }, dataLabelOverrides: [{ idx: 0, showValue: false }, { idx: 1, showValue: false }, { idx: 2, showValue: false }, { idx: 3, showValue: false }] },
    ],
    barOptions: { direction: "column", grouping: "stacked", gapWidth: 55 },
    hasLegend: true,
    legend: { position: "bottom", overlay: false, textStyle: { typeface: FONT, fontSize: 13, fill: C.gray } },
    ...chartAxes("Hechos", 720),
    chartFill: "none", plotAreaFill: "none", chartLine: { fill: "none", width: 0 },
  });
  styleChart(ch);
  text(s, "1,382 hechos de Delegaciones", 90, 605, 360, 32, 18, C.amber, true);
  text(s, "Representan 38.1% de los hechos registrados entre mayo y octubre", 455, 607, 650, 30, 16, C.gray);
  footer(s, 3);
  s.speakerNotes.text = "La unidad 2 comienza a aparecer en abril en actividades y en mayo en hechos. Por ello, el total combinado 2026 no debe compararse directamente con 2025 como si la cobertura fuera idéntica.";
}

// 4. Monthly intensity
{
  const s = baseSlide("La incidencia diaria conjunta bajó en octubre", "Promedio diario de las unidades 1 y 2");
  const ch = s.charts.add("line", {
    position: { left: 90, top: 170, width: 1080, height: 370 },
    categories: months,
    series: [{
      name: "Promedio diario",
      values: diarios2026,
      line: { style: "solid", fill: C.blue, width: 4 },
      marker: { symbol: "circle", size: 8, fill: C.blue },
      points: [{ idx: 8, fill: C.amber }, { idx: 9, fill: C.green }],
    }],
    hasLegend: false,
    ...chartAxes("Hechos por día", 24, "0.0"),
    dataLabels: { showValue: true, position: "top", numberFormatCode: "0.0", textStyle: { typeface: FONT, fontSize: 12, fill: C.navy, bold: true } },
    chartFill: "none", plotAreaFill: "none", chartLine: { fill: "none", width: 0 },
  });
  styleChart(ch);
  text(s, "21.70", 800, 565, 120, 42, 25, C.amber, true, "center");
  text(s, "septiembre", 800, 605, 120, 25, 12, C.gray, false, "center");
  text(s, "−1.1%", 942, 565, 120, 42, 25, C.green, true, "center");
  text(s, "variación diaria en octubre", 900, 605, 220, 25, 12, C.gray, false, "center");
  footer(s, 4);
  s.speakerNotes.text = "Septiembre registró 651 hechos conjuntos en 30 días: 21.70 diarios. Octubre registró 665 en 31 días: 21.45 diarios. El total mensual subió 2.2%, pero la intensidad diaria bajó 1.1%. El salto de mayo refleja la incorporación de Delegaciones a la cobertura.";
}

// 5. Weekly trend
{
  const s = baseSlide("Tendencia mensual por unidad", "La unidad 1 conserva la serie histórica y la unidad 2 inicia en mayo");
  const ch = s.charts.add("line", {
    position: { left: 78, top: 165, width: 1120, height: 360 },
    categories: months.slice(4),
    series: [
      { name: "Unidad 1 · Siniestros", values: hechos2026.slice(4), line: { style: "solid", fill: C.blue, width: 4 }, marker: { symbol: "circle", size: 7, fill: C.blue } },
      { name: "Unidad 2 · Delegaciones", values: delegaciones2026.slice(4), line: { style: "solid", fill: C.amber, width: 4 }, marker: { symbol: "circle", size: 7, fill: C.amber } },
    ],
    hasLegend: true,
    legend: { position: "bottom", overlay: false, textStyle: { typeface: FONT, fontSize: 12, fill: C.gray } },
    ...chartAxes("Hechos", 460),
    dataLabels: { showValue: true, position: "top", textStyle: { typeface: FONT, fontSize: 11, fill: C.navy, bold: true } },
    chartFill: "none", plotAreaFill: "none", chartLine: { fill: "none", width: 0 },
  });
  styleChart(ch);
  text(s, "415", 250, 580, 90, 38, 23, C.blue, true, "center");
  text(s, "Unidad 1 en octubre", 335, 585, 220, 26, 12, C.gray);
  text(s, "250", 750, 580, 90, 38, 23, C.amber, true, "center");
  text(s, "Unidad 2 en octubre", 835, 585, 220, 26, 12, C.gray);
  footer(s, 5);
  s.speakerNotes.text = "Unidad 1: 329, 329, 366, 318, 352, 359, 365, 340, 412 y 415. Unidad 2: 218, 243, 214, 218, 239 y 250 desde mayo.";
}

// 6. Activities by month
{
  const s = baseSlide("Escala operativa de ambas unidades", "Actividades mensuales registradas en producción");
  const ch1 = s.charts.add("bar", {
    position: { left: 70, top: 185, width: 535, height: 335 },
    categories: actividadMonths,
    series: [{ name: "Unidad 1", values: actividades, fill: C.blue }],
    barOptions: { direction: "column", grouping: "clustered", gapWidth: 48 },
    hasLegend: false,
    ...chartAxes("Actividades", 540),
    dataLabels: { showValue: true, position: "outEnd", textStyle: { typeface: FONT, fontSize: 10, fill: C.navy, bold: true } },
    chartFill: "none", plotAreaFill: "none", chartLine: { fill: "none", width: 0 },
  });
  styleChart(ch1);
  const ch2 = s.charts.add("bar", {
    position: { left: 665, top: 185, width: 535, height: 335 },
    categories: actividadMonths.slice(2),
    series: [{ name: "Unidad 2", values: actividadesDelegaciones.slice(2), fill: C.amber }],
    barOptions: { direction: "column", grouping: "clustered", gapWidth: 48 },
    hasLegend: false,
    ...chartAxes("Actividades", 15000),
    dataLabels: { showValue: true, position: "outEnd", numberFormatCode: "#,##0", textStyle: { typeface: FONT, fontSize: 9, fill: C.navy, bold: true } },
    chartFill: "none", plotAreaFill: "none", chartLine: { fill: "none", width: 0 },
  });
  styleChart(ch2);
  text(s, "Unidad 1 · Siniestros", 150, 148, 380, 28, 18, C.navy, true, "center");
  text(s, "Unidad 2 · Delegaciones", 745, 148, 380, 28, 18, C.navy, true, "center");
  text(s, "2,660 actividades", 150, 575, 350, 34, 20, C.blue, true, "center");
  text(s, "57,659 actividades", 750, 575, 350, 34, 20, C.amber, true, "center");
  text(s, "Las escalas operativas son distintas y se presentan por separado", 365, 625, 550, 26, 14, C.gray, false, "center");
  footer(s, 6);
  s.speakerNotes.text = "Producción, febrero–octubre 2026. Unidad 1: 2,660 actividades. Unidad 2: 57,659 actividades desde abril. Las diferencias de volumen reflejan modelos de captura y cobertura territorial distintos.";
}

// 7. Activity mix
{
  const s = baseSlide("Principales actividades por unidad", "Acumulado de producción a octubre de 2026");
  const ch1 = s.charts.add("bar", {
    position: { left: 70, top: 185, width: 535, height: 355 },
    categories: ["Apoyo vial", "Patrullajes", "Concientización", "Vacaciones Seguras", "Proximidad"],
    series: [{ name: "Unidad 1", values: [1039, 579, 202, 155, 128], fill: C.blue }],
    barOptions: { direction: "bar", grouping: "clustered", gapWidth: 42 },
    hasLegend: false,
    xAxis: { min: 0, max: 1150, textStyle: { typeface: FONT, fontSize: 10, fill: C.gray }, majorGridlines: { style: "solid", fill: C.grid, width: 1 }, line: { style: "solid", fill: C.grid, width: 1 } },
    yAxis: { textStyle: { typeface: FONT, fontSize: 11, fill: C.ink }, majorGridlines: null, line: { style: "solid", fill: C.grid, width: 1 } },
    dataLabels: { showValue: true, position: "outEnd", textStyle: { typeface: FONT, fontSize: 10, fill: C.navy, bold: true } },
    chartFill: "none", plotAreaFill: "none", chartLine: { fill: "none", width: 0 },
  });
  styleChart(ch1);
  const ch2 = s.charts.add("bar", {
    position: { left: 665, top: 185, width: 535, height: 355 },
    categories: ["Apoyo vial", "Escuelas", "Pasos peatonales", "Patrullajes", "Oficinas públicas"],
    series: [{ name: "Unidad 2", values: [8335, 6784, 6426, 5662, 3859], fill: C.amber }],
    barOptions: { direction: "bar", grouping: "clustered", gapWidth: 42 },
    hasLegend: false,
    xAxis: { min: 0, max: 9000, textStyle: { typeface: FONT, fontSize: 10, fill: C.gray }, majorGridlines: { style: "solid", fill: C.grid, width: 1 }, line: { style: "solid", fill: C.grid, width: 1 } },
    yAxis: { textStyle: { typeface: FONT, fontSize: 11, fill: C.ink }, majorGridlines: null, line: { style: "solid", fill: C.grid, width: 1 } },
    dataLabels: { showValue: true, position: "outEnd", numberFormatCode: "#,##0", textStyle: { typeface: FONT, fontSize: 10, fill: C.navy, bold: true } },
    chartFill: "none", plotAreaFill: "none", chartLine: { fill: "none", width: 0 },
  });
  styleChart(ch2);
  text(s, "Unidad 1 · Siniestros", 150, 148, 380, 28, 18, C.navy, true, "center");
  text(s, "Unidad 2 · Delegaciones", 745, 148, 380, 28, 18, C.navy, true, "center");
  text(s, "El liderazgo operativo debe comunicarse con indicadores diferenciados por unidad", 180, 595, 920, 45, 17, C.ink, true, "center");
  footer(s, 7);
  s.speakerNotes.text = "Principales categorías de producción consultadas por unidad. Los volúmenes no deben sumarse como si ambas unidades utilizaran el mismo modelo operativo.";
}

// 8. Resolution
{
  const s = baseSlide("Capacidad de resolución por unidad", "Porcentaje mensual de hechos resueltos en 2026");
  const ch = s.charts.add("line", {
    position: { left: 78, top: 170, width: 800, height: 380 },
    categories: months.slice(4),
    series: [
      { name: "Unidad 1 · Siniestros", values: tasaRes2026.slice(4), line: { style: "solid", fill: C.blue, width: 4 }, marker: { symbol: "circle", size: 7, fill: C.blue } },
      { name: "Unidad 2 · Delegaciones", values: tasaResDelegaciones2026.slice(4), line: { style: "solid", fill: C.amber, width: 4 }, marker: { symbol: "circle", size: 7, fill: C.amber } },
    ],
    hasLegend: true,
    legend: { position: "bottom", overlay: false, textStyle: { typeface: FONT, fontSize: 12, fill: C.gray } },
    xAxis: { textStyle: { typeface: FONT, fontSize: 11, fill: C.gray }, line: { style: "solid", fill: C.grid, width: 1 } },
    yAxis: { min: 0.5, max: 0.9, majorUnit: 0.1, numberFormatCode: "0%", textStyle: { typeface: FONT, fontSize: 11, fill: C.gray }, majorGridlines: { style: "solid", fill: C.grid, width: 1 }, line: { style: "solid", fill: C.grid, width: 1 } },
    chartFill: "none", plotAreaFill: "none", chartLine: { fill: "none", width: 0 },
  });
  styleChart(ch);
  text(s, "81.4%", 945, 205, 210, 62, 35, C.blue, true, "center");
  text(s, "Unidad 1", 945, 270, 210, 28, 15, C.ink, true, "center");
  text(s, "63.2%", 945, 330, 210, 62, 35, C.amber, true, "center");
  text(s, "Unidad 2", 945, 395, 210, 28, 15, C.ink, true, "center");
  text(s, "76.3%", 945, 470, 210, 50, 28, C.green, true, "center");
  text(s, "resultado conjunto", 945, 520, 210, 28, 13, C.gray, false, "center");
  text(s, "31 pendientes en todo el periodo", 915, 575, 270, 30, 16, C.navy, true, "center");
  footer(s, 8);
  s.speakerNotes.text = "Enero–octubre 2026. Unidad 1: 2,917 de 3,585 resueltos, 81.4%. Unidad 2: 874 de 1,382, 63.2%. Resultado conjunto: 3,791 de 4,967, 76.3%. La suma de estados principales no cubre 102 registros.";
}

// 9. Types
{
  const s = baseSlide("Principales tipos de hecho por unidad", "Enero–octubre de 2026");
  const cats = ["Corte de circulación", "Alcance", "Objeto fijo", "Cambio de carril", "Invasión de carril", "Salida de rodamiento"];
  const v1 = [871, 853, 479, 501, 182, 154];
  const v2 = [308, 207, 166, 20, 190, 200];
  const ch = s.charts.add("bar", {
    position: { left: 85, top: 165, width: 1080, height: 405 },
    categories: cats,
    series: [{ name: "Unidad 1 · Siniestros", values: v1, fill: C.blue }, { name: "Unidad 2 · Delegaciones", values: v2, fill: C.amber }],
    barOptions: { direction: "bar", grouping: "stacked", gapWidth: 55 },
    hasLegend: true,
    legend: { position: "bottom", overlay: false, textStyle: { typeface: FONT, fontSize: 12, fill: C.gray } },
    xAxis: { min: 0, max: 1050, textStyle: { typeface: FONT, fontSize: 11, fill: C.gray }, majorGridlines: { style: "solid", fill: C.grid, width: 1 }, line: { style: "solid", fill: C.grid, width: 1 } },
    yAxis: { textStyle: { typeface: FONT, fontSize: 12, fill: C.ink }, majorGridlines: null, line: { style: "solid", fill: C.grid, width: 1 } },
    dataLabels: { showValue: true, position: "center", textStyle: { typeface: FONT, fontSize: 10, fill: C.white, bold: true } },
    chartFill: "none", plotAreaFill: "none", chartLine: { fill: "none", width: 0 },
  });
  styleChart(ch);
  text(s, "1,179", 115, 603, 140, 35, 22, C.navy, true);
  text(s, "cortes de circulación", 250, 607, 260, 28, 14, C.gray);
  text(s, "1,060", 650, 603, 140, 35, 22, C.navy, true);
  text(s, "colisiones por alcance", 785, 607, 260, 28, 14, C.gray);
  footer(s, 9);
  s.speakerNotes.text = "Principales tipos combinados de 2026. Corte de circulación: 871 U1 y 308 U2. Alcance: 853 U1 y 207 U2. Objeto fijo: 479 U1 y 166 U2. Cambio de carril: 501 U1 y 20 U2. Invasión: 182 U1 y 190 U2. Salida de rodamiento: 154 U1 y 200 U2.";
}

// 10. Severity
{
  const s = baseSlide("La severidad requiere atención", "Lesionados, hospitalizados y fallecidos de ambas unidades");
  const ch1 = s.charts.add("line", {
    position: { left: 70, top: 175, width: 730, height: 360 },
    categories: months,
    series: [
      { name: "Lesionados", values: lesionados, line: { style: "solid", fill: C.blue, width: 4 }, marker: { symbol: "circle", size: 6, fill: C.blue } },
      { name: "Hospitalizados", values: hospitalizados, line: { style: "solid", fill: C.amber, width: 3 }, marker: { symbol: "circle", size: 5, fill: C.amber } },
    ],
    hasLegend: true,
    legend: { position: "bottom", overlay: false, textStyle: { typeface: FONT, fontSize: 12, fill: C.gray } },
    ...chartAxes("Personas", 150),
    chartFill: "none", plotAreaFill: "none", chartLine: { fill: "none", width: 0 },
  });
  styleChart(ch1);
  const ch2 = s.charts.add("bar", {
    position: { left: 840, top: 175, width: 345, height: 360 },
    categories: months,
    series: [{ name: "Fallecidos", values: fallecidos, fill: C.red }],
    barOptions: { direction: "column", grouping: "clustered", gapWidth: 45 },
    hasLegend: false,
    ...chartAxes("Fallecidos", 16),
    dataLabels: { showValue: true, position: "outEnd", textStyle: { typeface: FONT, fontSize: 10, fill: C.red, bold: true } },
    chartFill: "none", plotAreaFill: "none", chartLine: { fill: "none", width: 0 },
  });
  styleChart(ch2);
  text(s, "La severidad no respalda una mejora", 95, 580, 570, 30, 18, C.red, true);
  text(s, "El salto de mayo coincide con la incorporación de Delegaciones y no representa una comparación homogénea", 95, 618, 1080, 28, 14, C.gray);
  footer(s, 10);
  s.speakerNotes.text = "Enero–octubre 2026. Unidad 1: 330 lesionados, 19 fallecidos, 32 graves y 212 hospitalizados. Unidad 2: 428 lesionados, 29 fallecidos, 34 graves y 265 hospitalizados. Total: 758 lesionados y 48 fallecidos. La cobertura de unidad 2 comienza en mayo.";
}

// 11. Operations and incidence phases
{
  const s = baseSlide("Estabilización reciente y despliegue operativo", "Comparación de septiembre y octubre de 2026");
  const ch1 = s.charts.add("bar", {
    position: { left: 70, top: 185, width: 535, height: 330 },
    categories: ["Unidad 1", "Unidad 2", "Total"],
    series: [{ name: "Septiembre", values: [412, 239, 651], fill: C.lightBlue }, { name: "Octubre", values: [415, 250, 665], fill: C.blue }],
    barOptions: { direction: "column", grouping: "clustered", gapWidth: 55 },
    hasLegend: true,
    legend: { position: "bottom", overlay: false, textStyle: { typeface: FONT, fontSize: 11, fill: C.gray } },
    ...chartAxes("Hechos", 720),
    dataLabels: { showValue: true, position: "outEnd", textStyle: { typeface: FONT, fontSize: 12, fill: C.navy, bold: true } },
    chartFill: "none", plotAreaFill: "none", chartLine: { fill: "none", width: 0 },
  });
  styleChart(ch1);
  const ch2 = s.charts.add("bar", {
    position: { left: 665, top: 185, width: 535, height: 330 },
    categories: ["Unidad 1", "Unidad 2"],
    series: [{ name: "Índice octubre", values: [97.7, 107.6], fill: C.amber, points: [{ idx: 0, fill: C.blue }] }],
    barOptions: { direction: "column", grouping: "clustered", gapWidth: 55 },
    hasLegend: false,
    xAxis: { textStyle: { typeface: FONT, fontSize: 12, fill: C.gray }, line: { style: "solid", fill: C.grid, width: 1 } },
    yAxis: { min: 0, max: 120, majorUnit: 20, numberFormatCode: "0", textStyle: { typeface: FONT, fontSize: 11, fill: C.gray }, majorGridlines: { style: "solid", fill: C.grid, width: 1 }, line: { style: "solid", fill: C.grid, width: 1 } },
    dataLabels: { showValue: true, position: "outEnd", numberFormatCode: "0.0", textStyle: { typeface: FONT, fontSize: 12, fill: C.navy, bold: true } },
    chartFill: "none", plotAreaFill: "none", chartLine: { fill: "none", width: 0 },
  });
  styleChart(ch2);
  text(s, "Hechos registrados", 160, 148, 350, 28, 18, C.navy, true, "center");
  text(s, "Actividad de octubre · septiembre = 100", 700, 148, 460, 28, 18, C.navy, true, "center");
  text(s, "El total mensual subió 2.2%, pero la incidencia diaria conjunta bajó 1.1% en octubre", 95, 575, 1080, 48, 18, C.ink, true, "center");
  text(s, "La coincidencia temporal respalda una narrativa de contención, no una afirmación causal", 95, 625, 1080, 32, 14, C.gray, false, "center");
  footer(s, 11);
  s.speakerNotes.text = "Hechos: U1 412 a 415, U2 239 a 250, total 651 a 665. Actividades: U1 430 a 420, índice 97.7; U2 12,417 a 13,357, índice 107.6. Promedio diario conjunto: 21.70 a 21.45, una baja de 1.1%.";
}

// 12. Closing narrative
{
  const s = presentation.slides.add();
  s.background.fill = C.navy;
  rect(s, 70, 80, 8, 520, C.amber);
  text(s, "NARRATIVA EJECUTIVA", 110, 90, 500, 30, 14, C.lightBlue, true);
  text(s, "Cobertura ampliada,\nrespuesta diferenciada", 108, 145, 800, 120, 38, C.white, true);
  text(s, "El sistema registró 4,967 hechos entre enero y octubre. Delegaciones aportó 1,382 desde mayo. La cobertura conjunta exige evaluar cada unidad por separado y comunicar el total con transparencia.", 112, 300, 960, 115, 22, "#E8F0F6");
  text(s, "Mensaje recomendado", 112, 454, 260, 28, 16, C.amber, true);
  text(s, "“El liderazgo amplió la cobertura y sostuvo la capacidad de atención. En octubre, la incidencia diaria conjunta dejó de acelerar.”", 112, 495, 980, 70, 22, C.white, true);
  text(s, "Usar: cobertura ampliada, estabilización reciente y capacidad de respuesta por unidad", 112, 620, 1030, 24, 12, "#B8C7D6");
  text(s, "Evitar: comparar el total 2026 contra 2025 sin explicar la incorporación de Delegaciones", 112, 651, 1040, 24, 12, "#B8C7D6");
  text(s, "12", 1130, 674, 70, 18, 10, "#8FA7BA", true, "right");
  s.speakerNotes.text = "Cierre sugerido: reconocer la ampliación de cobertura desde mayo, diferenciar el desempeño de ambas unidades y centrar el liderazgo en capacidad operativa, seguimiento y estabilización reciente.";
}

await fs.mkdir(previewDir, { recursive: true });
await fs.mkdir(stagingDir, { recursive: true });
await fs.mkdir(path.dirname(finalPath), { recursive: true });

for (const [index, slide] of presentation.slides.items.entries()) {
  const png = await presentation.export({ slide, format: "png", scale: 1 });
  await fs.writeFile(path.join(previewDir, `slide-${String(index + 1).padStart(2, "0")}.png`), new Uint8Array(await png.arrayBuffer()));
}
const montage = await presentation.export({ format: "webp", montage: true });
await fs.writeFile(path.join(buildDir, "montage.webp"), new Uint8Array(await montage.arrayBuffer()));

const candidatePath = path.join(stagingDir, "candidate.pptx");
await (await PresentationFile.exportPptx(presentation)).save(candidatePath);

const result = await finalizePresentation({
  explicitTotalSlideCount: 12,
  requiredNativeTableOwnerSlides: [],
  requiredNativeChartOwnerSlides: [2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
  materializeLiteralChartWorkbooks: true,
  nativeChartTargetApplication: "powerpoint",
  workspaceDir,
  candidatePath,
  finalPath,
  pythonExecutable: RUNTIME_PYTHON,
  integrityValidatorPath: path.join(SKILL_DIR, "container_tools/inspect_presentation_package_integrity.py"),
  layoutValidatorPath: path.join(SKILL_DIR, "container_tools/inspect_presentation_layout_geometry.py"),
  layoutArgs: ["--expected-slide-size-emu", "12192000,6858000", "--validate-heading-fit"],
  fontPolicy: { basis: "design", families: [FONT] },
  verifyArtifactToolImport: true,
  receiptPath: path.join(stagingDir, "validation-produccion-unidades-1-2-v2.json"),
});

console.log(JSON.stringify({ finalPath, slideCount: presentation.slides.items.length, validation: result }, null, 2));

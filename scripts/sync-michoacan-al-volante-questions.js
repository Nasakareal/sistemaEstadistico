const fs = require('fs');
const path = require('path');

const source = process.argv[2];
const target = process.argv[3];

if (!source || !target) {
  throw new Error('Uso: node scripts/sync-michoacan-al-volante-questions.js <vial_question_bank.dart> <salida.json>');
}

const dart = fs.readFileSync(source, 'utf8');
const questionPattern = /VialQuestion\(\s*'((?:\\.|[^'\\])*)',\s*'((?:\\.|[^'\\])*)',\s*\[((?:.|\r|\n)*?)\],\s*(\d+),\s*'((?:\\.|[^'\\])*)',\s*illustration:\s*'((?:\\.|[^'\\])*)',\s*id:\s*'((?:\\.|[^'\\])*)',\s*\)/g;
const stringPattern = /'((?:\\.|[^'\\])*)'/g;

function decodeDartString(value) {
  return value
    .replace(/\\'/g, "'")
    .replace(/\\n/g, '\n')
    .replace(/\\r/g, '\r')
    .replace(/\\t/g, '\t')
    .replace(/\\\\/g, '\\');
}

const questions = [];
let match;

while ((match = questionPattern.exec(dart)) !== null) {
  const options = [];
  let optionMatch;
  stringPattern.lastIndex = 0;
  while ((optionMatch = stringPattern.exec(match[3])) !== null) {
    options.push(decodeDartString(optionMatch[1]));
  }

  questions.push({
    id: decodeDartString(match[7]),
    category: decodeDartString(match[1]),
    prompt: decodeDartString(match[2]),
    options,
    correct: Number(match[4]),
    explanation: decodeDartString(match[5]),
    illustration: decodeDartString(match[6]),
  });
}

if (questions.length < 30) {
  throw new Error(`Solo se pudieron extraer ${questions.length} preguntas; se esperaba el banco completo.`);
}

for (const question of questions) {
  if (question.options.length < 3 || question.correct < 0 || question.correct >= question.options.length) {
    throw new Error(`Pregunta invalida: ${question.id}`);
  }
}

fs.mkdirSync(path.dirname(target), { recursive: true });
fs.writeFileSync(target, `${JSON.stringify(questions, null, 2)}\n`, 'utf8');
process.stdout.write(`Sincronizadas ${questions.length} preguntas en ${target}\n`);

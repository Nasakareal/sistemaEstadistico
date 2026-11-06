(function () {
    'use strict';

    const root = document.querySelector('[data-app]');
    if (!root) return;

    const views = Object.fromEntries(
        Array.from(document.querySelectorAll('[data-view]')).map((view) => [view.dataset.view, view])
    );
    const academyContent = document.querySelector('[data-academy-content]');
    const academyTitle = document.querySelector('[data-academy-title]');
    const timerElement = document.querySelector('[data-timer]');
    const gameContent = document.querySelector('[data-game-content]');
    const gameTitle = document.querySelector('[data-game-title]');
    const gameScore = document.querySelector('[data-game-score]');
    const assetsUrl = root.dataset.assetsUrl.replace(/\/$/, '');
    const letters = ['A', 'B', 'C', 'D', 'E'];
    let questionBank = null;
    let timerId = null;
    let academyState = null;
    let gameState = null;

    const hazardScenarios = [
        { title: 'El balón perdido', hint: 'Observa junto a los vehículos estacionados a la derecha.', explanation: 'La pelota es la primera señal. Reduce la velocidad y prepárate: detrás puede aparecer una niña o un niño.', image: 'fondo_escuela.png', target: [69, 55, 18, 25] },
        { title: 'Puerta inesperada', hint: 'Una puerta puede abrirse hacia tu carril sin aviso.', explanation: 'Una puerta que comienza a abrirse invade la trayectoria. Conserva distancia lateral y anticipa la maniobra.', image: 'fondo_puerta.png', target: [64, 43, 20, 34] },
        { title: 'Cruce bajo la lluvia', hint: 'Vigila la banqueta antes de llegar al cruce.', explanation: 'La lluvia reduce la visibilidad y aumenta la distancia de frenado. Anticipa al peatón antes de que cruce.', image: 'fondo_lluvia.png', target: [66, 35, 18, 40] },
        { title: 'Moto entre vehículos', hint: 'Una motocicleta puede salir del punto ciego de la camioneta.', explanation: 'Una motocicleta puede ocultarse detrás de un vehículo grande. No cierres su trayectoria y deja espacio.', image: 'fondo_moto.png', target: [55, 34, 24, 43] },
    ];

    const priorityPuzzles = [
        { title: 'Giro y cruce peatonal', context: 'Un automóvil se prepara para girar a la derecha. Una persona ya comenzó a cruzar.', rule: 'La persona que cruza tiene preferencia. El conductor espera hasta que el paso esté libre.', image: 'escena_1.png', users: [{ id: 'peaton', label: 'Persona que cruza', detail: 'Ya inició el paso peatonal' }, { id: 'auto', label: 'Automóvil que gira', detail: 'Espera para incorporarse' }], solution: ['peaton', 'auto'] },
        { title: 'Preferencia en glorieta', context: 'Una moto va delante de una bicicleta dentro de la glorieta. Un auto espera para incorporarse.', rule: 'El auto cede a quienes ya circulan. En esta escena, la moto pasa antes que la bicicleta.', image: 'escena_2.png', users: [{ id: 'moto', label: 'Moto en circulación', detail: 'Va adelante dentro de la glorieta' }, { id: 'bici', label: 'Bicicleta en la glorieta', detail: 'Circula detrás de la moto' }, { id: 'auto', label: 'Auto que se incorpora', detail: 'Tiene señalamiento CEDA' }], solution: ['moto', 'bici', 'auto'] },
        { title: 'Paso de emergencia', context: 'Se acerca una ambulancia con señales activas. Una persona espera para cruzar y un auto está detenido.', rule: 'Se facilita el paso de la ambulancia. Después cruza la persona y el auto espera.', image: 'escena_3.png', users: [{ id: 'ambulancia', label: 'Ambulancia con sirena', detail: 'Se aproxima por la izquierda' }, { id: 'peaton', label: 'Persona que espera', detail: 'Cruza después de la ambulancia' }, { id: 'auto', label: 'Automóvil detenido', detail: 'Espera su oportunidad de girar' }], solution: ['ambulancia', 'peaton', 'auto'] },
        { title: 'Cruce con giro pendiente', context: 'La bicicleta ya entra desde la derecha. Después llega el auto recto. La moto espera para girar.', rule: 'Primero la bicicleta, después el auto que sigue recto y al final la moto que gira.', image: 'escena_4.png', users: [{ id: 'bici', label: 'Bicicleta a la derecha', detail: 'Ya está entrando al cruce' }, { id: 'auto_norte', label: 'Auto que sigue recto', detail: 'Circula hacia el sur' }, { id: 'moto_giro', label: 'Moto que espera para girar', detail: 'No debe atravesarse' }], solution: ['bici', 'auto_norte', 'moto_giro'] },
    ];

    const decisionScenes = [
        { title: 'El autobús oculta el cruce', condition: 'Tarde soleada · visibilidad parcial', clue: 'El autobús tapa a quien podría cruzar por delante.', image: 'fondo_autobus.png', choices: [['Reducir y preparar el frenado', 2, 'Reduciste antes del punto ciego. La persona cruza y conservas espacio para detenerte.'], ['Mantener la velocidad', 1, 'Detectas al peatón muy tarde y debes frenar de golpe.'], ['Rebasar al autobús', 0, 'Avanzas hacia una zona oculta y aumenta el riesgo de atropellamiento.']] },
        { title: 'Cochera con poca visibilidad', condition: 'Zona habitacional · tarde', clue: 'Un vehículo comienza a salir de una cochera a tu derecha.', image: 'fondo_cochera.png', choices: [['Soltar acelerador y dejar espacio', 2, 'Dejaste margen para que el vehículo termine de incorporarse.'], ['Acelerar para pasar primero', 0, 'Acortas la distancia cuando las trayectorias están por cruzarse.'], ['Tocar el claxon y seguir igual', 1, 'El claxon alerta, pero no sustituye reducir ni dejar espacio.']] },
        { title: 'Lluvia y reflejos', condition: 'Noche · pavimento mojado', clue: 'Las luces de freno aparecen sobre un pavimento resbaladizo.', image: 'fondo_lluvia.png', choices: [['Aumentar distancia y bajar velocidad', 2, 'Conservas distancia para frenar progresivamente.'], ['Seguir las luces del auto delantero', 1, 'Depender de sus movimientos deja menos tiempo para reaccionar.'], ['Usar luces altas y acelerar', 0, 'El brillo y la velocidad reducen tu margen de reacción.']] },
        { title: 'Motocicleta en punto ciego', condition: 'Avenida · tránsito moderado', clue: 'Una motocicleta avanza por el costado que quieres ocupar.', image: 'fondo_punto_ciego.png', choices: [['Esperar, señalizar y verificar', 2, 'Esperaste a que la motocicleta pasara antes de cambiar de carril.'], ['Cerrar el espacio lentamente', 0, 'Invades la trayectoria de la motocicleta y puedes derribarla.'], ['Girar mirando solamente el espejo', 1, 'El espejo no muestra todos los puntos ciegos.']] },
    ];

    function escapeHtml(value) {
        return String(value).replace(/[&<>'"]/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[character]));
    }

    function shuffle(items) {
        const copy = items.slice();
        for (let index = copy.length - 1; index > 0; index -= 1) {
            const randomIndex = Math.floor(Math.random() * (index + 1));
            [copy[index], copy[randomIndex]] = [copy[randomIndex], copy[index]];
        }
        return copy;
    }

    function showView(name) {
        Object.values(views).forEach((view) => view.classList.remove('is-active'));
        views[name].classList.add('is-active');
        document.body.dataset.mavView = name;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function goHome() {
        clearTimer();
        academyState = null;
        gameState = null;
        showView('home');
        history.replaceState(null, '', window.location.pathname);
    }

    async function loadQuestions() {
        if (questionBank) return questionBank;
        const response = await fetch(root.dataset.questionsUrl, { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error('No fue posible cargar el banco de preguntas.');
        questionBank = await response.json();
        return questionBank;
    }

    function clearTimer() {
        if (timerId) window.clearInterval(timerId);
        timerId = null;
        timerElement.hidden = true;
        timerElement.classList.remove('is-low');
    }

    async function openAcademy(mode) {
        showView('academy');
        academyTitle.textContent = mode === 'exam' ? 'Simulacro de examen' : mode === 'practice' ? 'Práctica rápida' : 'Aprender';
        academyContent.innerHTML = '<div class="setup-card"><div class="setup-icon">🚦</div><p>Cargando academia vial…</p></div>';
        clearTimer();
        try {
            await loadQuestions();
            renderAcademySetup(mode);
        } catch (error) {
            academyContent.innerHTML = `<div class="setup-card"><h2>No pudimos cargar la academia</h2><p>${escapeHtml(error.message)}</p><button class="button button-primary" data-home>Volver</button></div>`;
        }
    }

    function renderAcademySetup(mode) {
        const categories = Array.from(new Set(questionBank.map((question) => question.category)));
        if (mode === 'learn') {
            academyContent.innerHTML = `
                <div class="setup-card">
                    <div class="setup-icon">📖</div><h2>Elige un tema</h2>
                    <p>Estudia situaciones concretas. Cada respuesta muestra de inmediato por qué una decisión es más segura.</p>
                    <div class="topic-grid">
                        ${categories.map((category) => `<button class="topic-button" type="button" data-topic="${escapeHtml(category)}">${escapeHtml(category)} <small>→</small></button>`).join('')}
                    </div>
                    <button class="button button-primary" type="button" data-start-academy="learn">Repasar todos los temas</button>
                </div>`;
            return;
        }

        const exam = mode === 'exam';
        academyContent.innerHTML = `
            <div class="setup-card">
                <div class="setup-icon">${exam ? '⏱️' : '🎯'}</div>
                <h2>${exam ? 'Examen de entrenamiento' : 'Práctica rápida'}</h2>
                <p>${exam ? 'Contesta 30 preguntas sin ver las respuestas hasta el final. Puedes usar este resultado para identificar qué temas debes repasar.' : 'Resuelve 10 preguntas aleatorias y recibe una explicación después de cada respuesta.'}</p>
                <div class="setup-stats">
                    <span>${exam ? '30' : '10'} preguntas</span>
                    <span>${exam ? '25 minutos' : 'Sin límite de tiempo'}</span>
                    <span>${questionBank.length} preguntas en el banco</span>
                </div>
                <button class="button button-primary button-large" type="button" data-start-academy="${mode}">${exam ? 'Iniciar simulacro' : 'Comenzar práctica'}</button>
            </div>`;
    }

    function selectQuestions(mode, category) {
        const source = category ? questionBank.filter((question) => question.category === category) : questionBank;
        if (mode === 'exam') {
            const byCategory = Object.groupBy ? Object.groupBy(source, (question) => question.category) : source.reduce((groups, question) => ((groups[question.category] ||= []).push(question), groups), {});
            const buckets = Object.values(byCategory).map(shuffle);
            const result = [];
            while (result.length < 30 && buckets.some((bucket) => bucket.length)) {
                shuffle(buckets).forEach((bucket) => { if (bucket.length && result.length < 30) result.push(bucket.pop()); });
            }
            return shuffle(result);
        }
        if (mode === 'practice') return shuffle(source).slice(0, 10);
        return shuffle(source);
    }

    function startAcademy(mode, category) {
        academyState = {
            mode,
            questions: selectQuestions(mode, category),
            index: 0,
            answers: {},
            locked: false,
            seconds: mode === 'exam' ? 25 * 60 : null,
        };
        academyTitle.textContent = mode === 'exam' ? 'Simulacro de examen' : mode === 'practice' ? 'Práctica rápida' : (category || 'Aprender');
        if (mode === 'exam') startTimer();
        renderQuestion(true);
    }

    function startTimer() {
        timerElement.hidden = false;
        updateTimer();
        timerId = window.setInterval(() => {
            if (!academyState) return clearTimer();
            academyState.seconds -= 1;
            updateTimer();
            if (academyState.seconds <= 0) finishAcademy(true);
        }, 1000);
    }

    function updateTimer() {
        const seconds = Math.max(0, academyState.seconds);
        timerElement.textContent = `${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, '0')}`;
        timerElement.classList.toggle('is-low', seconds < 120);
    }

    function renderQuestion(resetScroll) {
        const state = academyState;
        const question = state.questions[state.index];
        const selected = state.answers[question.id];
        const showFeedback = state.mode !== 'exam' && state.locked;
        const image = `${assetsUrl}/academia/${question.illustration}.png`;
        academyContent.innerHTML = `
            <div class="question-shell">
                <div class="progress-row"><span>Pregunta ${state.index + 1} de ${state.questions.length}</span><span>${Math.round(((state.index + 1) / state.questions.length) * 100)}%</span></div>
                <div class="progress-track"><i style="width:${((state.index + 1) / state.questions.length) * 100}%"></i></div>
                <div class="question-grid">
                    <img class="question-image" src="${image}" alt="Ilustración de la situación vial" onerror="this.hidden=true">
                    <div class="question-copy">
                        <span class="category-chip">${escapeHtml(question.category)}</span>
                        <h2>${escapeHtml(question.prompt)}</h2>
                        <div class="answers">
                            ${question.options.map((option, index) => {
                                const classes = ['answer-button'];
                                if (selected === index) classes.push('is-selected');
                                if (showFeedback && index === question.correct) classes.push('is-correct');
                                if (showFeedback && selected === index && index !== question.correct) classes.push('is-wrong');
                                return `<button class="${classes.join(' ')}" type="button" data-answer="${index}" ${state.locked ? 'disabled' : ''}><span class="answer-letter">${letters[index]}</span><span>${escapeHtml(option)}</span></button>`;
                            }).join('')}
                        </div>
                        ${showFeedback ? `<div class="feedback ${selected === question.correct ? '' : 'wrong'}"><strong>${selected === question.correct ? '¡Correcto!' : 'Revisa esta decisión.'}</strong><br>${escapeHtml(question.explanation)}</div>` : ''}
                    </div>
                </div>
                <div class="question-actions">
                    <button class="button button-light" type="button" data-prev-question ${state.index === 0 || state.locked ? 'disabled' : ''}>Anterior</button>
                    <button class="button button-primary" type="button" data-next-question ${selected === undefined ? 'disabled' : ''}>${state.index + 1 === state.questions.length ? 'Ver resultado' : 'Siguiente'}</button>
                </div>
            </div>`;
        if (resetScroll) requestAnimationFrame(() => views.academy.scrollIntoView({ block: 'start', behavior: 'auto' }));
    }

    function answerQuestion(answer) {
        const state = academyState;
        const question = state.questions[state.index];
        if (state.locked) return;
        state.answers[question.id] = answer;
        if (state.mode !== 'exam') state.locked = true;
        renderQuestion(false);
    }

    function nextQuestion() {
        const state = academyState;
        const question = state.questions[state.index];
        if (state.answers[question.id] === undefined) return;
        if (state.index + 1 >= state.questions.length) return finishAcademy(false);
        state.index += 1;
        state.locked = false;
        renderQuestion(true);
    }

    function finishAcademy(timeExpired) {
        clearTimer();
        const state = academyState;
        const correct = state.questions.filter((question) => state.answers[question.id] === question.correct).length;
        const percent = Math.round((correct / state.questions.length) * 100);
        const goal = percent >= 80;
        saveProgress(state.mode, percent);
        academyContent.innerHTML = `
            <div class="result-card">
                <div class="result-ring" style="--score:${percent * 3.6}deg"><span>${percent}%</span></div>
                <h2>${timeExpired ? 'Se terminó el tiempo' : goal ? '¡Gran entrenamiento!' : 'Sigue practicando'}</h2>
                <p>Obtuviste <strong>${correct} de ${state.questions.length}</strong> respuestas correctas. ${goal ? 'Alcanzaste la meta de entrenamiento del 80%.' : 'Repasa las explicaciones y vuelve a intentarlo.'}</p>
                <div class="review-list">
                    ${state.questions.filter((question) => state.answers[question.id] !== question.correct).slice(0, 8).map((question) => `<div class="review-item wrong"><strong>${escapeHtml(question.prompt)}</strong><br><small>${escapeHtml(question.explanation)}</small></div>`).join('') || '<div class="review-item"><strong>¡No tuviste errores!</strong></div>'}
                </div>
                <p><small>El 80% es una meta de esta herramienta educativa, no el criterio de aprobación de un examen oficial.</small></p>
                <div class="hero-actions" style="justify-content:center">
                    <button class="button button-primary" type="button" data-restart-academy="${state.mode}">Intentar de nuevo</button>
                    <button class="button button-light" type="button" data-home>Volver al inicio</button>
                </div>
            </div>`;
    }

    function saveProgress(key, score) {
        try {
            const progress = JSON.parse(localStorage.getItem('michoacanAlVolanteProgress') || '{}');
            progress[key] = Math.max(progress[key] || 0, score);
            progress.updatedAt = new Date().toISOString();
            localStorage.setItem('michoacanAlVolanteProgress', JSON.stringify(progress));
        } catch (_) { /* El progreso local es opcional. */ }
    }

    function openGame(type) {
        clearTimer();
        showView('game');
        if (window.MAVGames) {
            window.MAVGames.open({
                type,
                content: gameContent,
                title: gameTitle,
                score: gameScore,
                assetsUrl,
                saveProgress,
            });
            return;
        }
        gameState = { type, level: 0, score: 0, resolved: false, mistakes: 0 };
        if (type === 'hazard') gameTitle.textContent = 'Detecta el peligro';
        if (type === 'priority') gameTitle.textContent = '¿Quién pasa primero?';
        if (type === 'decision') gameTitle.textContent = 'Un segundo antes';
        renderGame();
    }

    function renderGame() {
        gameScore.textContent = `${gameState.score} puntos`;
        if (gameState.type === 'hazard') renderHazardGame();
        if (gameState.type === 'priority') renderPriorityGame();
        if (gameState.type === 'decision') renderDecisionGame();
    }

    function renderHazardGame() {
        const scenario = hazardScenarios[gameState.level];
        const [left, top, width, height] = scenario.target;
        gameContent.innerHTML = `
            <div class="game-shell">
                <div class="progress-row"><span>Escena ${gameState.level + 1} de ${hazardScenarios.length}</span><span>${gameState.mistakes} intento(s) fallido(s)</span></div>
                <h2>${escapeHtml(scenario.title)}</h2><p class="game-intro">${escapeHtml(scenario.hint)} Toca la zona donde aparece la primera señal de peligro.</p>
                <div class="scene-image" data-hazard-scene style="background-image:url('${assetsUrl}/detecta_peligro/${scenario.image}')">
                    <button aria-label="Marcar peligro" class="hazard-target ${gameState.resolved ? 'is-revealed' : ''}" style="left:${left}%;top:${top}%;width:${width}%;height:${height}%" data-hazard-target></button>
                </div>
                ${gameState.resolved ? `<div class="game-feedback"><strong>¡Peligro detectado!</strong><br>${escapeHtml(scenario.explanation)}</div><div class="question-actions"><span></span><button class="button button-primary" type="button" data-next-game>${gameState.level + 1 === hazardScenarios.length ? 'Ver resultado' : 'Siguiente escena'}</button></div>` : '<p class="game-intro">Pista: busca señales tempranas, no esperes a que el riesgo esté frente al vehículo.</p>'}
            </div>`;
    }

    function resolveHazard(success) {
        if (gameState.resolved) return;
        if (success) {
            gameState.resolved = true;
            gameState.score += Math.max(100, 300 - gameState.mistakes * 50);
        } else {
            gameState.mistakes += 1;
        }
        renderGame();
    }

    function renderPriorityGame() {
        const puzzle = priorityPuzzles[gameState.level];
        if (!gameState.order) gameState.order = shuffle(puzzle.users);
        gameContent.innerHTML = `
            <div class="game-shell">
                <div class="progress-row"><span>Cruce ${gameState.level + 1} de ${priorityPuzzles.length}</span><span>Ordena quién pasa primero</span></div>
                <h2>${escapeHtml(puzzle.title)}</h2><p class="game-intro">${escapeHtml(puzzle.context)}</p>
                <div class="scene-image"><img src="${assetsUrl}/quien_pasa/${puzzle.image}" alt="Escena del cruce vial"></div>
                <div class="order-list">${gameState.order.map((user, index) => `<div class="order-item"><span class="order-rank">${index + 1}</span><span><strong>${escapeHtml(user.label)}</strong><small>${escapeHtml(user.detail)}</small></span><span class="order-controls"><button type="button" aria-label="Subir" data-move-order="${index}:-1" ${index === 0 || gameState.resolved ? 'disabled' : ''}>↑</button><button type="button" aria-label="Bajar" data-move-order="${index}:1" ${index + 1 === gameState.order.length || gameState.resolved ? 'disabled' : ''}>↓</button></span></div>`).join('')}</div>
                ${gameState.resolved ? `<div class="game-feedback"><strong>¡Orden correcto!</strong><br>${escapeHtml(puzzle.rule)}</div><div class="question-actions"><span></span><button class="button button-primary" type="button" data-next-game>${gameState.level + 1 === priorityPuzzles.length ? 'Ver resultado' : 'Siguiente cruce'}</button></div>` : `<div class="question-actions"><span></span><button class="button button-primary" type="button" data-check-order>Comprobar orden</button></div>`}
            </div>`;
    }

    function checkPriority() {
        const puzzle = priorityPuzzles[gameState.level];
        const correct = gameState.order.every((user, index) => user.id === puzzle.solution[index]);
        if (correct) {
            gameState.resolved = true;
            gameState.score += Math.max(150, 350 - gameState.mistakes * 50);
        } else {
            gameState.mistakes += 1;
            gameContent.insertAdjacentHTML('beforeend', '<div class="game-feedback bad" style="margin-top:12px"><strong>Ese orden todavía genera un conflicto.</strong> Revisa quién ya está dentro del cruce o tiene prioridad.</div>');
        }
        gameScore.textContent = `${gameState.score} puntos`;
        if (correct) renderGame();
    }

    function renderDecisionGame() {
        const scene = decisionScenes[gameState.level];
        gameContent.innerHTML = `
            <div class="game-shell">
                <div class="progress-row"><span>Decisión ${gameState.level + 1} de ${decisionScenes.length}</span><span>${escapeHtml(scene.condition)}</span></div>
                <h2>${escapeHtml(scene.title)}</h2><p class="game-intro">${escapeHtml(scene.clue)}</p>
                <div class="scene-image"><img src="${assetsUrl}/un_segundo_antes/${scene.image}" alt="Escena vial para tomar una decisión"></div>
                ${gameState.resolved ? `<div class="game-feedback ${gameState.lastSafety === 0 ? 'bad' : ''}"><strong>${gameState.lastSafety === 2 ? 'Decisión preventiva' : gameState.lastSafety === 1 ? 'Todavía hay riesgo' : 'Decisión peligrosa'}</strong><br>${escapeHtml(gameState.lastConsequence)}</div><div class="question-actions"><button class="button button-light" type="button" data-retry-decision>Probar otra acción</button><button class="button button-primary" type="button" data-next-game>${gameState.level + 1 === decisionScenes.length ? 'Ver resultado' : 'Siguiente escena'}</button></div>` : `<div class="decision-grid">${scene.choices.map((choice, index) => `<button class="decision-choice" type="button" data-decision="${index}">${escapeHtml(choice[0])}</button>`).join('')}</div>`}
            </div>`;
    }

    function chooseDecision(index) {
        if (gameState.resolved) return;
        const choice = decisionScenes[gameState.level].choices[index];
        gameState.resolved = true;
        gameState.lastSafety = choice[1];
        gameState.lastConsequence = choice[2];
        gameState.score += choice[1] * 150;
        if (choice[1] < 2) gameState.mistakes += 1;
        renderGame();
    }

    function nextGameLevel() {
        const total = gameState.type === 'hazard' ? hazardScenarios.length : gameState.type === 'priority' ? priorityPuzzles.length : decisionScenes.length;
        if (gameState.level + 1 >= total) return renderGameResult(total);
        gameState.level += 1;
        gameState.resolved = false;
        gameState.order = null;
        gameState.lastSafety = null;
        renderGame();
    }

    function renderGameResult(total) {
        saveProgress(`game_${gameState.type}`, gameState.score);
        gameContent.innerHTML = `
            <div class="result-card">
                <div class="setup-icon">🏁</div><h2>¡Reto completado!</h2>
                <p>Terminaste ${total} escenarios y obtuviste <strong>${gameState.score} puntos</strong>. ${gameState.mistakes === 0 ? 'Tomaste decisiones seguras en el primer intento.' : `Tuviste ${gameState.mistakes} oportunidad(es) de aprendizaje.`}</p>
                <div class="hero-actions" style="justify-content:center"><button class="button button-primary" type="button" data-restart-game="${gameState.type}">Jugar otra vez</button><button class="button button-light" type="button" data-home>Volver a los juegos</button></div>
            </div>`;
    }

    document.addEventListener('click', (event) => {
        const home = event.target.closest('[data-home]');
        if (home) { event.preventDefault(); goHome(); return; }
        const academy = event.target.closest('[data-open-academy]');
        if (academy) { openAcademy(academy.dataset.openAcademy); return; }
        const topic = event.target.closest('[data-topic]');
        if (topic) { startAcademy('learn', topic.dataset.topic); return; }
        const start = event.target.closest('[data-start-academy]');
        if (start) { startAcademy(start.dataset.startAcademy); return; }
        const answer = event.target.closest('[data-answer]');
        if (answer) { answerQuestion(Number(answer.dataset.answer)); return; }
        if (event.target.closest('[data-next-question]')) { nextQuestion(); return; }
        if (event.target.closest('[data-prev-question]') && academyState.index > 0) { academyState.index -= 1; academyState.locked = false; renderQuestion(true); return; }
        const restartAcademy = event.target.closest('[data-restart-academy]');
        if (restartAcademy) { startAcademy(restartAcademy.dataset.restartAcademy); return; }
        const game = event.target.closest('[data-open-game]');
        if (game) { openGame(game.dataset.openGame); return; }
        if (event.target.closest('[data-hazard-target]')) { event.stopPropagation(); resolveHazard(true); return; }
        if (event.target.closest('[data-hazard-scene]')) { resolveHazard(false); return; }
        const move = event.target.closest('[data-move-order]');
        if (move) {
            const [index, direction] = move.dataset.moveOrder.split(':').map(Number);
            const nextIndex = index + direction;
            [gameState.order[index], gameState.order[nextIndex]] = [gameState.order[nextIndex], gameState.order[index]];
            renderGame(); return;
        }
        if (event.target.closest('[data-check-order]')) { checkPriority(); return; }
        const decision = event.target.closest('[data-decision]');
        if (decision) { chooseDecision(Number(decision.dataset.decision)); return; }
        if (event.target.closest('[data-retry-decision]')) { gameState.resolved = false; renderGame(); return; }
        if (event.target.closest('[data-next-game]')) { nextGameLevel(); return; }
        const restartGame = event.target.closest('[data-restart-game]');
        if (restartGame) openGame(restartGame.dataset.restartGame);
    });
})();

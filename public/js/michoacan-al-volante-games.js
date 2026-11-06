(function () {
    'use strict';

    const colors = { blue: '#147ba6', navy: '#102d47', green: '#16865a', red: '#c83f4e', yellow: '#ffd64a' };
    let app = null;
    let state = null;
    let animationFrame = 0;
    let feedbackTimer = 0;

    const hazards = [
        { title: 'El balón perdido', hint: 'Observa junto a los vehículos estacionados a la derecha.', explanation: 'La pelota es la primera señal. Reduce la velocidad y prepárate: detrás puede aparecer una niña o un niño.', image: 'fondo_escuela.png', type: 'child', revealAt: .26, idealAt: .43, dangerAt: .78 },
        { title: 'Puerta inesperada', hint: 'Una puerta puede abrirse hacia tu carril sin aviso.', explanation: 'Una puerta que comienza a abrirse invade la trayectoria. Conserva distancia lateral y anticipa la maniobra.', image: 'fondo_puerta.png', type: 'door', revealAt: .29, idealAt: .44, dangerAt: .75 },
        { title: 'Cruce bajo la lluvia', hint: 'Vigila la banqueta antes de llegar al cruce.', explanation: 'La lluvia reduce la visibilidad y aumenta la distancia de frenado. Anticipa al peatón antes de que cruce.', image: 'fondo_lluvia.png', type: 'pedestrian', revealAt: .32, idealAt: .48, dangerAt: .79, rain: true },
        { title: 'Moto entre vehículos', hint: 'Una motocicleta puede salir del punto ciego de la camioneta.', explanation: 'Una motocicleta puede ocultarse detrás de un vehículo grande. No cierres su trayectoria y deja espacio.', image: 'fondo_moto.png', type: 'motorcycle', revealAt: .49, idealAt: .61, dangerAt: .82 },
    ];

    const puzzles = [
        { title: 'Giro y cruce peatonal', context: 'Un automóvil se prepara para girar a la derecha. Una persona comienza a cruzar la calle a la que ingresará.', rule: 'La persona que cruza tiene preferencia. El conductor espera hasta que el paso esté libre antes de girar.', image: 'escena_1.png', users: [
            actor('peaton', 'A', 'Persona que cruza', 'Ya inició el paso peatonal', 'peaton.png', colors.green, 77, 109, [[730,311,Math.PI],[730,397,Math.PI],[730,489,Math.PI],[730,629,Math.PI]]),
            actor('auto', 'B', 'Automóvil que gira', 'Espera para incorporarse', 'auto_azul.png', colors.blue, 102, 150, [[577,801,0],[577,660,0],[576,552,0],[609,519,Math.PI/4],[715,516,Math.PI/2],[903,516,Math.PI/2]])
        ], solution: ['peaton', 'auto'] },
        { title: 'Preferencia en glorieta', context: 'Una moto va adelante de una bicicleta dentro de la glorieta. Un auto espera para incorporarse desde el acceso sur.', rule: 'El auto cede a quienes ya circulan en la glorieta. En este ejercicio, la moto pasa antes que la bicicleta porque va delante.', image: 'escena_2.png', users: [
            actor('moto', 'A', 'Moto en circulación', 'Va adelante dentro de la glorieta', 'moto.png', colors.red, 79, 122, [[680,302,-.62],[614,244,-.99],[518,218,-1.50],[413,236,-2],[312,310,-2.40]]),
            actor('bici', 'B', 'Bicicleta en la glorieta', 'Circula detrás de la moto', 'bici.png', colors.green, 72, 113, [[718,500,-.15],[716,395,-.28],[667,309,-.68],[572,242,-1.24],[468,221,-1.66]]),
            actor('auto', 'C', 'Auto que se incorpora', 'Tiene señalamiento CEDA', 'auto_amarillo.png', colors.blue, 99, 150, [[556,815,0],[556,736,0],[596,648,.42],[678,587,.42],[730,497,-.03],[840,509,Math.PI/2],[956,509,Math.PI/2]])
        ], solution: ['moto', 'bici', 'auto'] },
        { title: 'Paso de emergencia', context: 'Se acerca una ambulancia con señales activas. Una persona espera para cruzar y un auto permanece detenido.', rule: 'Se facilita el paso de la ambulancia sin generar otro riesgo. Cuando ha pasado, la persona puede cruzar y el auto espera.', image: 'escena_3.png', users: [
            actor('ambulancia', 'A', 'Ambulancia con sirena', 'Se aproxima por la izquierda', 'ambulancia.png', colors.red, 109, 161, [[99,510,Math.PI/2],[278,510,Math.PI/2],[494,510,Math.PI/2],[728,510,Math.PI/2],[944,510,Math.PI/2]]),
            actor('peaton', 'B', 'Persona que espera', 'Cruza después de la ambulancia', 'peaton.png', colors.green, 77, 109, [[735,311,Math.PI],[735,406,Math.PI],[735,521,Math.PI],[735,631,Math.PI]]),
            actor('auto', 'C', 'Automóvil detenido', 'Espera su oportunidad de girar', 'auto_turquesa.png', colors.blue, 100, 151, [[567,800,0],[567,663,0],[567,551,0],[612,521,Math.PI/4],[727,520,Math.PI/2],[927,520,Math.PI/2]])
        ], solution: ['ambulancia', 'peaton', 'auto'] },
        { title: 'Cruce con giro pendiente', context: 'La bicicleta ya entra al cruce desde la derecha. Después llega el auto que sigue recto. La moto espera para girar.', rule: 'Aquí la bicicleta ya inició el cruce; después pasa el auto recto y por último gira la moto. En la calle real revisa señalamiento, posiciones y llegada.', image: 'escena_4.png', users: [
            actor('bici', 'A', 'Bicicleta a la derecha', 'Ya está entrando al cruce', 'bici.png', colors.green, 76, 118, [[747,351,-Math.PI/2],[631,351,-Math.PI/2],[480,351,-Math.PI/2],[336,351,-Math.PI/2],[187,351,-Math.PI/2]]),
            actor('auto_norte', 'B', 'Auto que sigue recto', 'Circula hacia el sur', 'auto_azul.png', colors.blue, 103, 151, [[421,112,Math.PI],[421,270,Math.PI],[421,431,Math.PI],[421,605,Math.PI],[421,792,Math.PI]]),
            actor('moto_giro', 'C', 'Moto que espera para girar', 'No debe atravesarse', 'moto.png', colors.red, 76, 125, [[578,814,0],[578,652,0],[577,560,0],[610,514,Math.PI/4],[712,512,Math.PI/2],[920,512,Math.PI/2]])
        ], solution: ['bici', 'auto_norte', 'moto_giro'] },
    ];

    const decisions = [
        { title: 'El autobús oculta el cruce', condition: 'Tarde soleada · visibilidad parcial', type: 'bus', background: 'fondo_autobus.png', clue: 'El autobús tapa a quien podría cruzar por delante.', choices: [['Reducir y preparar el frenado',2,'Reduciste la velocidad antes del punto ciego. La persona cruza y conservas espacio para detenerte.'],['Mantener la velocidad',1,'Detectas al peatón muy tarde. Debes frenar de golpe para evitar el conflicto.'],['Rebasar al autobús',0,'Avanzas hacia una zona oculta sin comprobar el paso peatonal. El riesgo de atropellamiento aumenta.']] },
        { title: 'Cochera con poca visibilidad', condition: 'Zona habitacional · tarde', type: 'driveway', background: 'fondo_cochera.png', clue: 'Un vehículo comienza a salir de una cochera a tu derecha.', choices: [['Soltar acelerador y dejar espacio',2,'Dejaste margen para que el vehículo termine de incorporarse sin cruzarse con tu trayectoria.'],['Acelerar para pasar primero',0,'Acortas la distancia justo cuando el vehículo sale. Las trayectorias se cruzan y debes hacer una maniobra brusca.'],['Tocar el claxon y seguir igual',1,'El claxon alerta, pero no sustituye reducir la velocidad ni mantener espacio de seguridad.']] },
        { title: 'Lluvia y reflejos', condition: 'Noche · pavimento mojado', type: 'rain', background: 'fondo_lluvia.png', clue: 'Las luces de freno aparecen sobre un pavimento resbaladizo.', choices: [['Aumentar distancia y bajar velocidad',2,'Conservas distancia suficiente para frenar de forma progresiva cuando el auto de adelante reduce la marcha.'],['Seguir las luces del auto delantero',1,'Depender de sus movimientos deja menos tiempo para reaccionar, especialmente cuando el piso está mojado.'],['Usar luces altas y acelerar',0,'El brillo y la velocidad reducen tu margen de reacción. Te acercas peligrosamente al vehículo que frena.']] },
        { title: 'Motocicleta en punto ciego', condition: 'Avenida · tránsito moderado', type: 'blindSpot', background: 'fondo_punto_ciego.png', clue: 'Una motocicleta avanza por el costado que quieres ocupar.', choices: [['Esperar, señalizar y verificar',2,'Esperaste a que la motocicleta pasara antes de cambiar de carril y evitaste cerrarle el camino.'],['Cerrar el espacio lentamente',0,'Aunque lo hagas despacio, invades la trayectoria de la motocicleta y puedes derribarla.'],['Girar mirando solamente el espejo',1,'El espejo no muestra todos los puntos ciegos. La motocicleta queda demasiado cerca de tu maniobra.']] },
    ];

    function actor(id, tag, label, detail, sprite, color, width, height, path) { return { id, tag, label, detail, sprite, color, width, height, path }; }
    function h(value) { return String(value).replace(/[&<>'"]/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c])); }
    function clamp(value, min, max) { return Math.max(min, Math.min(max, value)); }
    function smooth(value) { return value * value * (3 - 2 * value); }
    function motion(progress, start, end) { return smooth(clamp((progress - start) / (end - start), 0, 1)); }
    function seededShuffle(items, seed) { const out = items.slice(); let n = seed; for (let i = out.length - 1; i; i--) { n = (n * 9301 + 49297) % 233280; const j = Math.floor((n / 233280) * (i + 1)); [out[i], out[j]] = [out[j], out[i]]; } return out; }
    function stopAnimation() { cancelAnimationFrame(animationFrame); clearTimeout(feedbackTimer); animationFrame = 0; }
    function score(value) { app.score.textContent = `${value} pts`; }
    function asset(folder, file) { return `${app.assetsUrl}/${folder}/${file}`; }

    function open(config) {
        stopAnimation();
        app = config;
        if (config.type === 'hazard') initHazard();
        if (config.type === 'priority') initPriority();
        if (config.type === 'decision') initDecision();
    }

    function progressHeader(label, level, total, aside) {
        return `<div class="mav-level-head"><span class="mav-pill">${label} ${String(level + 1).padStart(2,'0')}</span><span>${aside || `${level + 1} / ${total}`}</span></div><div class="mav-level-progress"><i style="width:${((level + 1) / total) * 100}%"></i></div>`;
    }

    function tutorial(icon, kicker, title, copy, note, action, button) {
        return `<div class="mav-tutorial"><div class="mav-tutorial-inner"><div class="mav-tutorial-icon">${icon}</div><b>${kicker}</b><h2>${title}</h2><p>${copy}</p>${note ? `<small>${note}</small>` : ''}<button type="button" class="mav-action mav-action-green" ${action}>${button}</button></div></div>`;
    }

    // Detecta el peligro ----------------------------------------------------
    function initHazard() {
        app.title.textContent = 'Detecta el peligro';
        state = { game: 'hazard', level: 0, score: 0, streak: 0, mistakes: 0, hits: 0, resolved: false, hit: false, tutorial: true, progress: 0, feedback: '' };
        score(0); renderHazard();
    }

    function hazardRect(type, progress) {
        let t;
        if (type === 'child') {
            if (progress >= .46) { t = motion(progress,.46,.92); return { x:.728-.206*t, y:.580+.116*t, w:.085+.042*t, hh:.123+.060*t }; }
            t = motion(progress,.26,.78); const d=.036+.044*t; return { x:.716-.155*t, y:.640+.108*t, w:d, hh:d };
        }
        if (type === 'door') { t=motion(progress,.22,.66); return { x:(.681-.105*t+.700)/2, y:(.617-.015*t+.700+.006*t)/2, w:.700-(.681-.105*t), hh:(.700+.006*t)-(.617-.015*t) }; }
        if (type === 'pedestrian') { t=motion(progress,.32,.91); return { x:.148+.37*t, y:.604+.066*t, w:.113+.039*t, hh:.170+.041*t }; }
        t=motion(progress,.36,.88); return { x:.686-.165*t, y:.607+.172*t, w:.097+.084*t, hh:.129+.105*t };
    }

    function hazardSprite(file, rect, className) {
        return `<img class="mav-hazard-sprite ${className || ''}" src="${asset('detecta_peligro',file)}" style="left:${(rect.x-rect.w/2)*100}%;top:${(rect.y-rect.hh/2)*100}%;width:${rect.w*100}%;height:${rect.hh*100}%" alt="">`;
    }

    function renderHazard() {
        stopAnimation();
        const scene = hazards[state.level];
        app.content.innerHTML = `<div class="mav-game mav-hazard-game">
            ${progressHeader('ESCENA', state.level, hazards.length, `${state.streak >= 2 ? `🔥 COMBO x${state.streak} · ` : ''}${state.level+1} / ${hazards.length}`)}
            <div class="mav-game-heading"><h2>${h(scene.title)}</h2><p>${h(scene.hint)}</p></div>
            <div class="mav-hazard-wrap"><div class="mav-hazard-scene" data-mav-hazard-scene>
                <img class="mav-scene-bg" src="${asset('detecta_peligro',scene.image)}" alt="Escena vial: ${h(scene.title)}">
                <div data-mav-hazard-layers></div>
                <div class="mav-scene-shade"></div><div class="mav-scene-chip">◉ OBSERVA</div><div class="mav-countdown" data-mav-countdown>8 s</div>
                <div class="mav-timebar"><i data-mav-timebar></i></div>
            </div></div>
            <div class="mav-bottom-panel" data-mav-feedback>${state.resolved ? hazardResult(scene) : '<strong>☝ Toca el peligro en cuanto aparezca</strong>'}</div>
            ${state.tutorial ? tutorial('👁','MISIÓN: ANTICIPAR','Observa la calle.<br>Encuentra el peligro.','Toca el riesgo apenas lo identifiques. Ganas más puntos si te anticipas, y pierdes por falsas alarmas.','Cada escena dura 8 segundos.','data-mav-hazard-start','COMENZAR MISIÓN') : ''}
        </div>`;
        bindHazard();
        if (!state.tutorial && !state.resolved) startHazardTimeline();
        if (state.resolved) drawHazardFrame();
    }

    function hazardResult(scene) {
        return `<div class="mav-result-copy ${state.hit ? 'good' : 'bad'}"><strong>${state.hit ? '✓' : '!'} ${h(state.feedback)}</strong><p>${h(scene.explanation)}</p><button type="button" class="mav-action" data-mav-hazard-next>${state.level + 1 === hazards.length ? 'Ver resultado' : 'Siguiente escena'}</button></div>`;
    }

    function bindHazard() {
        const start = app.content.querySelector('[data-mav-hazard-start]');
        if (start) start.onclick = () => { state.tutorial=false; renderHazard(); };
        const scene = app.content.querySelector('[data-mav-hazard-scene]');
        if (scene) scene.onclick = onHazardTap;
        const next = app.content.querySelector('[data-mav-hazard-next]');
        if (next) next.onclick = nextHazard;
    }

    function startHazardTimeline() {
        state.startedAt = performance.now() - state.progress * 8000;
        const frame = (now) => {
            state.progress = clamp((now - state.startedAt) / 8000, 0, 1);
            drawHazardFrame();
            if (state.progress >= 1) { timeoutHazard(); return; }
            animationFrame = requestAnimationFrame(frame);
        };
        animationFrame = requestAnimationFrame(frame);
    }

    function drawHazardFrame() {
        const scene = hazards[state.level];
        const layers = app.content.querySelector('[data-mav-hazard-layers]');
        if (!layers) return;
        const p = state.progress;
        let html = '';
        if (scene.type === 'child') {
            if (p >= .26) { const t=motion(p,.26,.78), d=.036+.044*t; html += hazardSprite('balon.png',{x:.716-.155*t,y:.640+.108*t,w:d,hh:d}); }
            if (p >= .46) html += hazardSprite('nina.png',hazardRect('child',p));
            html += `<img class="mav-occluder" src="${asset('detecta_peligro','oclusor_escuela.png')}" alt="">`;
        }
        if (scene.type === 'door' && p >= .22) html += hazardSprite('puerta_abierta.png',hazardRect('door',p),'mav-door');
        if (scene.type === 'pedestrian' && p >= .32) html += hazardSprite('peaton.png',hazardRect('pedestrian',p));
        if (scene.type === 'motorcycle') {
            if (p >= .36) html += hazardSprite('moto_perspectiva.png',hazardRect('motorcycle',p));
            html += `<img class="mav-occluder" src="${asset('detecta_peligro','oclusor_moto.png')}" alt="">`;
        }
        if (scene.rain) html += '<div class="mav-rain"></div>';
        if (state.resolved) { const r=hazardRect(scene.type,p); html += `<span class="mav-risk-marker ${state.hit?'good':'bad'}" style="left:${r.x*100}%;top:${r.y*100}%">${state.hit?'✓':'!'}</span>`; }
        layers.innerHTML = html;
        const countdown=app.content.querySelector('[data-mav-countdown]'); if(countdown){ countdown.textContent=`${Math.ceil(8*(1-p))} s`; countdown.classList.toggle('urgent',p>.625&&!state.resolved); }
        const bar=app.content.querySelector('[data-mav-timebar]'); if(bar){ bar.style.width=`${(1-p)*100}%`; bar.className=state.resolved?(state.hit?'good':'bad'):(p>.72?'urgent':''); }
    }

    function onHazardTap(event) {
        if (state.resolved || state.tutorial) return;
        const scene = hazards[state.level];
        const bounds = event.currentTarget.getBoundingClientRect();
        const x=(event.clientX-bounds.left)/bounds.width, y=(event.clientY-bounds.top)/bounds.height;
        const p=state.progress, r=hazardRect(scene.type,p);
        let valid=p>=scene.revealAt && Math.abs(x-r.x)<=r.w/2+.03 && Math.abs(y-r.y)<=r.hh/2+.025;
        if(scene.type==='motorcycle' && r.x-r.w/2>=.628) valid=false;
        if(valid){
            stopAnimation(); const anticipation=clamp((scene.dangerAt-p)/(scene.dangerAt-scene.revealAt),0,1); const gained=180+Math.round(anticipation*320)+Math.min(state.streak,4)*60;
            state.resolved=true; state.hit=true; state.hits++; state.streak++; state.score+=gained; state.feedback=p<=scene.idealAt?`¡Excelente anticipación! +${gained}`:anticipation>.35?`¡Buen ojo! +${gained}`:`¡Riesgo detectado! +${gained}`; score(state.score); renderHazard();
        } else {
            state.mistakes++; state.streak=0; state.score=Math.max(0,state.score-45); score(state.score);
            const panel=app.content.querySelector('[data-mav-feedback]'); if(panel) panel.innerHTML='<strong class="bad">⚠ Falsa alarma · −45 puntos</strong>';
            clearTimeout(feedbackTimer); feedbackTimer=setTimeout(()=>{const current=app.content.querySelector('[data-mav-feedback]'); if(current&&!state.resolved) current.innerHTML='<strong>☝ Toca el peligro en cuanto aparezca</strong>';},950);
        }
    }

    function timeoutHazard(){ if(state.resolved)return; state.mistakes++;state.streak=0;state.resolved=true;state.hit=false;state.feedback='¡El peligro te sorprendió!';renderHazard(); }
    function nextHazard(){ if(state.level+1===hazards.length){showResult('detecta_peligro','Detecta el peligro',state.score,state.hits,state.mistakes,state.hits===4&&state.mistakes===0?3:state.hits>=3?2:1,'peligros detectados');return;} state.level++;state.resolved=false;state.hit=false;state.feedback='';state.progress=0;renderHazard(); }

    // ¿Quién pasa primero? -------------------------------------------------
    function initPriority(){ app.title.textContent='¿Quién pasa primero?'; state={game:'priority',level:0,score:0,mistakes:0,solved:0,checked:false,correct:false,tutorial:true,progress:0}; loadPuzzle();score(0);renderPriority(); }
    function loadPuzzle(){ const puzzle=puzzles[state.level]; state.order=seededShuffle(puzzle.users,state.level+7);if(state.order.map(x=>x.id).join('|')===puzzle.solution.join('|'))state.order.reverse(); }
    function poseAt(user, progress){ const path=user.path;if(progress<=0)return path[0];if(progress>=1)return path[path.length-1];const step=progress*(path.length-1),i=Math.min(path.length-2,Math.floor(step)),u=step-i,a=path[i],b=path[i+1],before=path[Math.max(0,i-1)],after=path[Math.min(path.length-1,i+2)];const cat=(p0,p1,p2,p3)=>.5*((2*p1)+(-p0+p2)*u+(2*p0-5*p1+4*p2-p3)*u*u+(-p0+3*p1-3*p2+p3)*u*u*u);let delta=b[2]-a[2];while(delta>Math.PI)delta-=Math.PI*2;while(delta<-Math.PI)delta+=Math.PI*2;return[cat(before[0],a[0],b[0],after[0]),cat(before[1],a[1],b[1],after[1]),a[2]+delta*smooth(u)]; }

    function renderPriority(){ stopAnimation();const puzzle=puzzles[state.level];app.content.innerHTML=`<div class="mav-game mav-priority-game">
        ${progressHeader('CRUCE',state.level,puzzles.length,`🛡 ${state.solved} resueltos · ${state.level+1} / ${puzzles.length}`)}
        <div class="mav-game-heading"><h2>${h(puzzle.title)}</h2><p>${h(puzzle.context)}</p></div>
        <div class="mav-priority-layout"><div><div class="mav-map"><div class="mav-map-inner"><img class="mav-scene-bg" src="${asset('quien_pasa',puzzle.image)}" alt="Vista aérea del cruce"><div data-mav-actors></div><span class="mav-map-tag">⌖ VISTA AÉREA</span><span class="mav-map-status" data-mav-map-status></span></div></div><p class="mav-map-note">ⓘ Las letras del mapa corresponden a las fichas. No indican prioridad.</p></div>
        <div class="mav-order-panel"><h3>↝ ELIGE EL ORDEN DE PASO</h3><p>Arrastra desde el asa o usa las flechas para reordenar.</p><div class="mav-order-list" data-mav-order-list>${priorityRows(puzzle)}</div>${priorityFeedback(puzzle)}<button type="button" class="mav-action ${state.checked&&!state.correct?'mav-action-red':''}" data-mav-priority-action>${!state.checked?'▶ Comprobar y ver el cruce':state.correct?(state.level+1===puzzles.length?'Ver resultado':'Siguiente cruce'):'↻ Reordenar e intentar otra vez'}</button></div></div>
        ${state.tutorial?tutorial('↝','','¡TÚ CONTROLAS EL CRUCE!','Observa el mapa, lee la situación y ordena quién debe pasar primero. Las fichas se arrastran y también tienen flechas.','Si aciertas, verás cómo atraviesan el cruce en el orden correcto.','data-mav-priority-start','¡Comenzar misión!'):''}
    </div>`;bindPriority();drawPriorityActors();if(state.checked)animatePriority(); }

    function priorityRows(puzzle){return state.order.map((user,index)=>{const right=state.checked&&user.id===puzzle.solution[index];return `<div class="mav-order-item ${state.checked?(right?'right':'wrong'):''}" draggable="${!state.checked}" data-order-index="${index}"><b>${index+1}</b><span class="mav-user-tag" style="--tag:${user.color}">${user.tag}</span><span><strong>${h(user.label)}</strong><small>${h(user.detail)}</small></span>${state.checked?`<i>${right?'✓':'✕'}</i>`:`<span class="mav-order-tools"><button type="button" data-mav-move="${index}:-1" ${index===0?'disabled':''}>↑</button><button type="button" data-mav-move="${index}:1" ${index===state.order.length-1?'disabled':''}>↓</button><em>⠿</em></span>`}</div>`;}).join('');}
    function priorityFeedback(puzzle){if(!state.checked)return'';return `<div class="mav-order-feedback ${state.correct?'good':'bad'}"><strong>${state.correct?'✓ ¡Cruce resuelto!':'⚠ Todavía hay un conflicto'}</strong><p>${h(state.correct?puzzle.rule:'Revisa quién ya está circulando, quién espera y quién debe ceder. Cambia el orden e inténtalo otra vez.')}</p></div>`;}
    function bindPriority(){const start=app.content.querySelector('[data-mav-priority-start]');if(start)start.onclick=()=>{state.tutorial=false;renderPriority();};app.content.querySelectorAll('[data-mav-move]').forEach(btn=>btn.onclick=()=>{const[i,d]=btn.dataset.mavMove.split(':').map(Number);movePriority(i,i+d);});const action=app.content.querySelector('[data-mav-priority-action]');if(action)action.onclick=priorityAction;let dragged=null;app.content.querySelectorAll('[data-order-index]').forEach(row=>{row.ondragstart=()=>{dragged=Number(row.dataset.orderIndex);row.classList.add('dragging');};row.ondragend=()=>row.classList.remove('dragging');row.ondragover=e=>e.preventDefault();row.ondrop=e=>{e.preventDefault();const target=Number(row.dataset.orderIndex);if(dragged!==null&&dragged!==target)movePriority(dragged,target);};});}
    function movePriority(from,to){if(state.checked||to<0||to>=state.order.length)return;const[item]=state.order.splice(from,1);state.order.splice(to,0,item);renderPriority();}
    function priorityAction(){if(!state.checked){checkPriority();return;}if(!state.correct){state.checked=false;state.correct=false;state.progress=0;renderPriority();return;}if(state.level+1===puzzles.length){showResult('quien_pasa','¿Quién pasa primero?',state.score,state.solved,state.mistakes,state.solved===4&&state.mistakes===0?3:state.solved>=3?2:1,'cruces resueltos');return;}state.level++;state.checked=false;state.correct=false;state.progress=0;loadPuzzle();renderPriority();}
    function checkPriority(){const puzzle=puzzles[state.level];state.correct=state.order.every((user,i)=>user.id===puzzle.solution[i]);state.checked=true;if(state.correct){state.solved++;state.score+=300+Math.max(0,120-state.mistakes*15);}else state.mistakes++;score(state.score);renderPriority();}
    function actorMarkup(user,pose,active,pulse){return `<div class="mav-map-actor ${active?'active':''}" style="left:${pose[0]/10}%;top:${pose[1]/8.8}%;width:${user.width/10}%;height:${user.height/8.8}%;--pulse:${pulse};--tag:${user.color}"><img src="${asset('quien_pasa',user.sprite)}" style="transform:rotate(${pose[2]}rad)" alt="${h(user.label)}"><b>${user.tag}</b></div>`;}
    function drawPriorityActors(){const puzzle=puzzles[state.level],box=app.content.querySelector('[data-mav-actors]');if(!box)return;box.innerHTML=puzzle.users.map(user=>{const index=state.order.findIndex(x=>x.id===user.id),local=state.checked&&state.correct?clamp(state.progress*state.order.length-index,0,1):0,active=state.checked&&state.correct&&local>0&&local<1,pulse=state.checked&&!state.correct?Math.abs(Math.sin(state.progress*Math.PI*5))*.5+.3:0;return actorMarkup(user,poseAt(user,local),active,pulse);}).join('');const status=app.content.querySelector('[data-mav-map-status]');if(status&&state.checked){status.textContent=state.correct?'✓ ORDEN CORRECTO':'⚠ REVISA EL ORDEN';status.className=`mav-map-status ${state.correct?'good':'bad'}`;}}
    function animatePriority(){const duration=5100,start=performance.now();const frame=now=>{state.progress=clamp((now-start)/duration,0,1);drawPriorityActors();if(state.progress<1)animationFrame=requestAnimationFrame(frame);};animationFrame=requestAnimationFrame(frame);}

    // Un segundo antes -----------------------------------------------------
    function initDecision(){app.title.textContent='Un segundo antes';state={game:'decision',level:0,score:0,mistakes:0,safe:0,tutorial:true,choice:null,alreadyScored:false,reviewing:false,progress:0,loop:true};makeChoiceOrder();score(0);renderDecision();}
    function makeChoiceOrder(){state.choiceOrder=seededShuffle([0,1,2],73+state.level*31);}
    function renderDecision(){stopAnimation();const scene=decisions[state.level],choice=state.choice===null?null:scene.choices[state.choice];app.content.innerHTML=`<div class="mav-game mav-decision-game">
        ${progressHeader('ESCENA',state.level,decisions.length)}
        <div class="mav-decision-layout"><div><div class="mav-decision-scene" data-mav-decision-scene><img class="mav-scene-bg" src="${asset('un_segundo_antes',scene.background)}" alt="${h(scene.title)}"><div data-mav-decision-objects></div><canvas data-mav-effects></canvas><span class="mav-map-tag">${choice?'▶ DESENLACE':'◉ OBSERVA LA PISTA'}</span><span class="mav-decision-status">${choice?(choice[1]===2?'PREVENCIÓN':choice[1]===1?'CASI ACCIDENTE':'RIESGO ALTO'):'ANTES DE ACTUAR'}</span><div class="mav-timebar"><i data-mav-decision-bar></i></div></div><p class="mav-map-note">${choice?'💡 Observa la trayectoria y compara el margen de seguridad.':`⌕ ${h(scene.clue)}`}</p></div>
        <div class="mav-decision-panel"><h2>${h(scene.title)}</h2><p>${h(scene.condition)}</p>${decisionPanel(scene,choice)}</div></div>
        ${state.tutorial?tutorial('◴','','Cambia el desenlace','Observa el riesgo, elige qué harías y mira cómo cambia la escena. Puedes rebobinar para aprender de las otras decisiones.','Solo la primera decisión de cada escena suma puntos.','data-mav-decision-start','Comenzar recorrido'):''}
    </div>`;bindDecision();startDecisionAnimation();}
    function decisionPanel(scene,choice){if(!choice)return `<h3>¿Qué haces un segundo antes?</h3><div class="mav-choice-list">${state.choiceOrder.map((original,i)=>`<button type="button" data-mav-choice="${original}"><b>${String.fromCharCode(65+i)}</b>${h(scene.choices[original][0])}</button>`).join('')}</div>${state.reviewing?'<small>Modo práctica: puedes probar otra decisión, sin sumar puntos otra vez.</small>':''}`;const safety=choice[1],title=safety===2?'¡Elegiste prevenir!':safety===1?'Te faltó margen de reacción':'La maniobra generó peligro';return `<div class="mav-consequence safety-${safety}"><i>${safety===2?'✓':safety===1?'⚠':'!'}</i><strong>${title}</strong><p>${h(choice[2])}</p></div><button type="button" class="mav-secondary" data-mav-rewind>↻ Retroceder y probar otra acción</button><button type="button" class="mav-action" data-mav-decision-next>${state.level+1===decisions.length?'Ver resultado':'Continuar recorrido'}</button>`;}
    function bindDecision(){const start=app.content.querySelector('[data-mav-decision-start]');if(start)start.onclick=()=>{state.tutorial=false;renderDecision();};app.content.querySelectorAll('[data-mav-choice]').forEach(btn=>btn.onclick=()=>chooseDecision(Number(btn.dataset.mavChoice)));const rewind=app.content.querySelector('[data-mav-rewind]');if(rewind)rewind.onclick=()=>{state.choice=null;state.reviewing=true;state.loop=true;renderDecision();};const next=app.content.querySelector('[data-mav-decision-next]');if(next)next.onclick=nextDecision;}
    function chooseDecision(index){if(state.choice!==null)return;const choice=decisions[state.level].choices[index];state.choice=index;state.loop=false;if(!state.alreadyScored){state.alreadyScored=true;if(choice[1]===2){state.safe++;state.score+=420;}else if(choice[1]===1){state.mistakes++;state.score+=180;}else state.mistakes++;}score(state.score);renderDecision();}
    function nextDecision(){if(state.level+1===decisions.length){showResult('segundo_antes','Un segundo antes',state.score,state.safe,state.mistakes,state.safe===4&&state.mistakes===0?3:state.safe>=3?2:1,'decisiones preventivas');return;}state.level++;state.choice=null;state.reviewing=false;state.alreadyScored=false;state.loop=true;makeChoiceOrder();renderDecision();}
    function spriteMarkup(name,x,y,w,hh,angle){return `<img class="mav-decision-sprite" src="${asset('un_segundo_antes',name)}" style="left:${x/9.6}%;top:${y/7.2}%;width:${w/9.6}%;height:${hh/7.2}%;transform:rotate(${angle||0}rad)" alt="">`;}
    function decisionObjects(scene,p,safety){const selected=safety!==null,t=smooth(p),advance=selected?t:0,preview=selected?0:p,risky=selected&&safety===0,medium=selected&&safety===1;let out='';if(scene.type==='bus'){out+=spriteMarkup('peaton.png',684-(selected?252*advance:65*preview),338+(selected?45*advance:8*preview),93+13*advance,147+18*advance);out+=spriteMarkup('autobus.png',611,287,208,290);out+=spriteMarkup('capo_auto.png',318+(risky?54*advance:medium?19*advance:0),648-(risky?78*advance:medium?27*advance:0),326,130);}if(scene.type==='driveway'){out+=spriteMarkup('auto_lateral.png',796-(selected?276*advance:35*preview),370+(selected?98*advance:13*preview),206+27*advance,110+20*advance,-.11);out+=spriteMarkup('capo_auto.png',317+(risky?30*advance:0),648-(risky?83*advance:medium?25*advance:0),326,130);}if(scene.type==='rain'){out+=spriteMarkup('auto_frenando.png',421-(selected?12*advance:2*preview),298+(selected?(safety===2?45:safety===1?91:133)*advance:7*preview),118+(selected?(safety===2?22:safety===1?46:69)*advance:0),155+(selected?(safety===2?29:safety===1?61:96)*advance:0));out+=spriteMarkup('capo_auto.png',318,646-(risky?63*advance:medium?19*advance:0),326,130);}if(scene.type==='blindSpot'){out+=spriteMarkup('auto_amarillo.png',345,295,126,165);out+=spriteMarkup('motocicleta.png',590-(selected?46*advance:9*preview),467-(selected?(safety===2?194:safety===1?98:52)*advance:20*preview),113-(selected?23*advance:0),151-(selected?32*advance:0));out+=spriteMarkup('auto_azul.png',331+(selected?(safety===2?0:safety===1?64:139)*advance:0),501-(selected?15*advance:0),164,207,selected?(safety===2?0:.05*advance):0);}return out;}
    function startDecisionAnimation(){const scene=decisions[state.level],choice=state.choice===null?null:scene.choices[state.choice],safety=choice?choice[1]:null,start=performance.now(),duration=3400;const frame=now=>{state.progress=clamp((now-start)/duration,0,1);drawDecisionFrame(scene,safety);if(state.progress<1)animationFrame=requestAnimationFrame(frame);else if(state.loop){state.progress=0;startDecisionAnimation();}};animationFrame=requestAnimationFrame(frame);}
    function drawDecisionFrame(scene,safety){const objects=app.content.querySelector('[data-mav-decision-objects]');if(objects)objects.innerHTML=decisionObjects(scene,state.progress,safety);const bar=app.content.querySelector('[data-mav-decision-bar]');if(bar){bar.style.width=`${(safety===null?1-state.progress:state.progress)*100}%`;bar.className=safety===2?'good':safety===1?'medium':safety===0?'bad':'';}const canvas=app.content.querySelector('[data-mav-effects]');if(!canvas)return;const rect=canvas.getBoundingClientRect(),dpr=window.devicePixelRatio||1;canvas.width=rect.width*dpr;canvas.height=rect.height*dpr;const ctx=canvas.getContext('2d');ctx.scale(dpr*rect.width/960,dpr*rect.height/720);if(scene.type==='rain'){ctx.strokeStyle='rgba(184,222,239,.61)';ctx.lineWidth=2.5;for(let i=0;i<63;i++){const x=(i*131+state.progress*190)%990-15,y=(i*87+state.progress*770)%770-25;ctx.beginPath();ctx.moveTo(x,y);ctx.lineTo(x-16,y+32);ctx.stroke();}}if(safety!==null&&state.progress>.43){const reveal=clamp((state.progress-.43)/.4,0,1),em=Math.sin(reveal*Math.PI/2),centers={bus:[478,474],driveway:[630,516],rain:[493,528],blindSpot:[540,497]},center=centers[scene.type],color=safety===2?'22,134,90':safety===1?'255,166,45':'200,63,78',radius=64+24*em;ctx.beginPath();ctx.arc(center[0],center[1],radius,0,Math.PI*2);ctx.fillStyle=`rgba(${color},${.17*em})`;ctx.fill();ctx.beginPath();ctx.arc(center[0],center[1],radius*.82,0,Math.PI*2);ctx.strokeStyle=`rgba(${color},${.7*em})`;ctx.lineWidth=6;ctx.stroke();if(state.progress>=.74){ctx.fillStyle=`rgb(${color})`;ctx.font='900 61px sans-serif';ctx.textAlign='center';ctx.fillText(safety===2?'✓':'!',center[0],center[1]+22);}}}

    function showResult(id,title,total,correct,mistakes,stars,label){stopAnimation();app.saveProgress(`game_${id}`,total);app.content.innerHTML=`<div class="mav-game-result"><div class="mav-stars">${[1,2,3].map(n=>`<span class="${n<=stars?'on':''}">★</span>`).join('')}</div><span>MISIÓN COMPLETADA</span><h2>${h(title)}</h2><strong>${total} puntos</strong><div class="mav-result-stats"><div><b>${correct}</b><small>${h(label)}</small></div><div><b>${mistakes}</b><small>errores</small></div><div><b>${stars}/3</b><small>estrellas</small></div></div><button type="button" class="mav-action" data-mav-replay>Jugar otra vez</button><button type="button" class="mav-secondary" data-home>Volver a los juegos</button></div>`;app.content.querySelector('[data-mav-replay]').onclick=()=>open({...app,type:state.game});}

    window.MAVGames = { open, stop: stopAnimation };
})();

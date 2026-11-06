(function () {
    'use strict';

    const colors = { blue: '#147ba6', navy: '#102d47', green: '#16865a', red: '#c83f4e', yellow: '#ffd64a' };
    let app = null;
    let state = null;
    let animationFrame = 0;
    let feedbackTimer = 0;
    let audioContext = null;
    let audioNodes = [];

    const hazards = [
        { title: 'El balón perdido', hint: 'Observa junto a los vehículos estacionados a la derecha.', explanation: 'La pelota es la primera señal. Reduce la velocidad y prepárate: detrás puede aparecer una niña o un niño.', image: 'fondo_escuela_corregido.png', type: 'child', revealAt: .26, idealAt: .43, dangerAt: .78 },
        { title: 'Puerta inesperada', hint: 'Una puerta puede abrirse hacia tu carril sin aviso.', explanation: 'Una puerta que comienza a abrirse invade la trayectoria. Conserva distancia lateral y anticipa la maniobra.', image: 'fondo_puerta.png', type: 'door', revealAt: .29, idealAt: .44, dangerAt: .75 },
        { title: 'Cruce bajo la lluvia', hint: 'Vigila la banqueta antes de llegar al cruce.', explanation: 'La lluvia reduce la visibilidad y aumenta la distancia de frenado. Anticipa al peatón antes de que cruce.', image: 'fondo_lluvia_corregido.png', type: 'pedestrian', revealAt: .32, idealAt: .48, dangerAt: .79, rain: true },
        { title: 'Moto entre vehículos', hint: 'Una motocicleta puede salir del punto ciego de la camioneta.', explanation: 'Una motocicleta puede ocultarse detrás de un vehículo grande. No cierres su trayectoria y deja espacio.', image: 'fondo_moto.png', type: 'motorcycle', revealAt: .49, idealAt: .61, dangerAt: .82 },
        { title: 'Cruce oculto por autobús', hint: 'El transporte detenido bloquea parte del paso peatonal.', explanation: 'Un autobús puede ocultar por completo a una persona. Reduce la velocidad antes de rebasar su frente y conserva margen para detenerte.', image: 'fondo_autobus_corregido.png', folder: 'un_segundo_antes', type: 'bus', revealAt: .25, idealAt: .41, dangerAt: .76, landscape: true, stage: 2 },
        { title: 'Salida de cochera', hint: 'Observa el costado derecho de la zona habitacional.', explanation: 'El frente de un vehículo asomándose es una advertencia temprana. Suelta el acelerador y evita colocarte en su trayectoria.', image: 'fondo_cochera.png', folder: 'un_segundo_antes', type: 'driveway', revealAt: .20, idealAt: .38, dangerAt: .74, landscape: true, stage: 2 },
        { title: 'Frenado sobre pavimento mojado', hint: 'Las luces y la distancia con el vehículo delantero son la clave.', explanation: 'Cuando el vehículo de adelante reduce la marcha sobre piso mojado necesitas más distancia. Anticipa el frenado y evita una maniobra brusca.', image: 'fondo_lluvia_v2.png', folder: 'un_segundo_antes', type: 'brake', revealAt: .30, idealAt: .47, dangerAt: .77, landscape: true, rain: true, stage: 2 },
        { title: 'Cambio de carril y punto ciego', hint: 'Una motocicleta se aproxima por el costado derecho antes de que cambies de carril.', explanation: 'La motocicleta aparece desde la zona que el espejo no cubre por completo. Señaliza, verifica el punto ciego y espera a que termine de adelantarte.', image: 'fondo_punto_ciego_v2.png', folder: 'un_segundo_antes', type: 'blindspot', revealAt: .34, idealAt: .52, dangerAt: .80, landscape: true, stage: 2 },
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
        { title: 'El autobús oculta el cruce', condition: 'Tarde soleada · visibilidad parcial', type: 'bus', background: 'fondo_autobus_corregido.png', clue: 'El autobús tapa a quien podría cruzar por delante.', choices: [['Reducir y preparar el frenado',2,'Reduciste la velocidad antes del punto ciego. La persona cruza y conservas espacio para detenerte.'],['Mantener la velocidad',1,'Detectas al peatón muy tarde. Debes frenar de golpe para evitar el conflicto.'],['Rebasar al autobús',0,'Avanzas hacia una zona oculta sin comprobar el paso peatonal. El riesgo de atropellamiento aumenta.']] },
        { title: 'Cochera con poca visibilidad', condition: 'Zona habitacional · tarde', type: 'driveway', background: 'fondo_cochera.png', clue: 'Un vehículo comienza a salir de una cochera a tu derecha.', choices: [['Soltar acelerador y dejar espacio',2,'Dejaste margen para que el vehículo termine de incorporarse sin cruzarse con tu trayectoria.'],['Acelerar para pasar primero',0,'Acortas la distancia justo cuando el vehículo sale. Las trayectorias se cruzan y debes hacer una maniobra brusca.'],['Tocar el claxon y seguir igual',1,'El claxon alerta, pero no sustituye reducir la velocidad ni mantener espacio de seguridad.']] },
        { title: 'Lluvia y reflejos', condition: 'Noche · pavimento mojado', type: 'rain', background: 'fondo_lluvia_v2.png', clue: 'Las luces de freno aparecen sobre un pavimento resbaladizo.', choices: [['Aumentar distancia y bajar velocidad',2,'Conservas distancia suficiente para frenar de forma progresiva cuando el auto de adelante reduce la marcha.'],['Seguir las luces del auto delantero',1,'Depender de sus movimientos deja menos tiempo para reaccionar, especialmente cuando el piso está mojado.'],['Usar luces altas y acelerar',0,'El brillo y la velocidad reducen tu margen de reacción. Te acercas peligrosamente al vehículo que frena.']] },
        { title: 'Motocicleta en punto ciego', condition: 'Avenida de tres carriles · tránsito moderado', type: 'blindSpot', background: 'fondo_punto_ciego_v2.png', clue: 'La motocicleta aparece desde atrás por el carril derecho y comienza a adelantarte.', choices: [['Esperar, señalizar y verificar',2,'Esperaste a que la motocicleta pasara antes de cambiar de carril y evitaste cerrarle el camino.'],['Cerrar el espacio lentamente',0,'Aunque lo hagas despacio, invades la trayectoria de la motocicleta y puedes derribarla.'],['Girar mirando solamente el espejo',1,'El espejo no muestra todos los puntos ciegos. La motocicleta queda demasiado cerca de tu maniobra.']] },
    ];

    const journeyRisks = [
        routeRisk('Zona escolar','🏫','Llegas a una escuela a la hora de salida. Hay personas cerca de la banqueta.','school',['Reducir la velocidad y cubrir el freno',2,'Anticipaste movimientos inesperados y conservaste espacio para detenerte.'],['Seguir al límite de la avenida',1,'El límite general puede ser excesivo frente a una escuela con actividad.'],['Acelerar antes de que crucen',0,'Aumentaste el riesgo justo donde pueden aparecer niñas y niños.']),
        routeRisk('Balón en la calle','⚽','Un balón rueda desde detrás de un vehículo estacionado.','school',['Frenar progresivamente y esperar',2,'El balón puede anunciar que una niña o un niño saldrá detrás.'],['Esquivar sin reducir',1,'Cambiar la trayectoria no elimina el riesgo oculto.'],['Tocar el claxon y continuar',0,'El claxon no sustituye detenerte ante un peligro inmediato.']),
        routeRisk('Paso peatonal','🚶','Una persona se aproxima a un cruce marcado sin semáforo.','city',['Ceder el paso y detenerte antes de la cebra',2,'Dejaste libre el cruce y protegiste a la persona.'],['Pasar despacio frente a ella',1,'Seguir avanzando puede intimidar o bloquear su trayectoria.'],['Pasar primero porque aún no pisa la calle',0,'Debes anticipar y respetar la prioridad peatonal.']),
        routeRisk('Semáforo amarillo','🚦','La luz cambia a amarillo y puedes detenerte sin una frenada brusca.','city',['Detenerte antes de la línea',2,'Tomaste la opción previsible y segura.'],['Acelerar para alcanzar a pasar',0,'Convertiste una advertencia en una maniobra de riesgo.'],['Frenar en medio del cruce',1,'La decisión tardía puede bloquear la intersección.']),
        routeRisk('Semáforo en rojo','🔴','El semáforo está en rojo, pero la calle transversal parece vacía.','city',['Detenerte completamente',2,'La ausencia aparente de tránsito no elimina la obligación de parar.'],['Reducir y cruzar mirando',0,'Cruzar en rojo mantiene un conflicto invisible.'],['Seguir al vehículo de adelante',1,'Cada conductor debe comprobar y respetar la señal.']),
        routeRisk('Vehículo de emergencia','🚑','Una ambulancia se aproxima con luces y sirena activas.','emergency',['Orillarte gradualmente y dejarla pasar',2,'Liberaste una trayectoria clara sin crear otro peligro.'],['Frenar de golpe en tu carril',1,'Detenerte sin acomodarte puede bloquear su paso.'],['Acelerar para ir delante',0,'Retrasas la atención de emergencia y aumentas el riesgo.']),
        routeRisk('Ciclista adelante','🚲','Alcanzas a una bicicleta que circula cerca del borde derecho.','park',['Esperar espacio y rebasar con distancia lateral',2,'Protegiste al ciclista frente a viento o movimientos inesperados.'],['Pasar muy cerca pero despacio',1,'La baja velocidad no compensa la falta de separación.'],['Usar el claxon para que se quite',0,'Puedes asustarlo y provocar una caída.']),
        routeRisk('Moto en el costado','🏍️','Una motocicleta avanza por la zona que no cubre bien tu espejo.','city',['Señalizar, verificar y esperar',2,'Comprobaste el punto ciego antes de cambiar de carril.'],['Moverte lentamente hacia ella',0,'Invadir despacio sigue siendo invadir su trayectoria.'],['Mirar solamente el espejo',1,'El espejo deja zonas sin visibilidad.']),
        routeRisk('Autobús detenido','🚌','Un autobús baja pasajeros y oculta parte de la banqueta.','city',['Reducir y prepararte para detenerte',2,'Anticipaste que alguien puede cruzar frente al autobús.'],['Rebasarlo a la misma velocidad',0,'Entraste sin margen a una zona sin visibilidad.'],['Pitar antes de pasar',1,'Avisar no sustituye reducir ni observar.']),
        routeRisk('Puerta de automóvil','🚪','Una persona permanece dentro de un auto estacionado junto a tu carril.','city',['Dejar distancia lateral y reducir',2,'Creaste margen por si abre la puerta.'],['Pasar pegado para no invadir otro carril',0,'La puerta puede abrirse directamente en tu trayectoria.'],['Acelerar para pasar rápido',1,'Menos tiempo junto al auto también significa menos reacción.']),
        routeRisk('Lluvia intensa','🌧️','La lluvia reduce la visibilidad y el pavimento comienza a acumular agua.','rain',['Bajar velocidad y aumentar distancia',2,'Adaptaste conducción y frenado a la adherencia disponible.'],['Mantener la velocidad con intermitentes',1,'Las intermitentes no recuperan visibilidad ni adherencia.'],['Frenar tarde para no perder ritmo',0,'En mojado la distancia de detención aumenta.']),
        routeRisk('Charco profundo','💦','Un charco ocupa parte del carril y no conoces su profundidad.','rain',['Reducir antes y cruzarlo suavemente',2,'Evitaste perder dirección o salpicar a otras personas.'],['Girar bruscamente al otro carril',0,'La maniobra repentina puede causar una colisión.'],['Entrar rápido para salir pronto',1,'La velocidad aumenta el riesgo de acuaplaneo.']),
        routeRisk('Conducción nocturna','🌙','La calle está oscura y un vehículo viene en sentido contrario.','night',['Usar luces bajas y moderar velocidad',2,'Evitaste deslumbrar y mantuviste alcance de reacción.'],['Encender luces altas de forma permanente',0,'Puedes cegar a otros conductores.'],['Guiarte por las luces del otro auto',1,'Debes conservar referencia propia y distancia.']),
        routeRisk('Banco de niebla','🌫️','Entras a una zona con visibilidad muy reducida.','fog',['Reducir y usar luces bajas',2,'Las luces bajas disminuyen el reflejo y la velocidad te da margen.'],['Usar luces altas para ver más',0,'La luz se refleja en la niebla y empeora la visión.'],['Seguir muy cerca al auto delantero',1,'Usarlo como guía elimina tu distancia de seguridad.']),
        routeRisk('Obras en la vía','🚧','Un señalamiento reduce dos carriles a uno.','works',['Reducir y alternar el paso con anticipación',2,'La incorporación ordenada evita frenadas y conflictos.'],['Cerrar el espacio para que nadie entre',0,'Bloquear aumenta el riesgo y la congestión.'],['Cambiar de carril en el último cono',1,'Esperar hasta el final reduce el margen de todos.']),
        routeRisk('Animal en el camino','🐕','Un perro se acerca al borde de la calle.','park',['Reducir y estar listo para detenerte',2,'Los animales pueden cambiar de dirección repentinamente.'],['Tocar el claxon de forma continua',1,'Puedes asustarlo y hacerlo correr hacia la vía.'],['Acelerar para pasar antes',0,'No puedes predecir su movimiento.']),
        routeRisk('Camión adelante','🚚','Circulas detrás de un camión que limita tu campo visual.','highway',['Aumentar distancia para recuperar visibilidad',2,'Ganaste tiempo y campo visual sin rebasar a ciegas.'],['Acercarte para aprovechar su avance',0,'Pierdes visibilidad y espacio de frenado.'],['Asomarte invadiendo el carril contrario',1,'Expones parte del vehículo sin confirmar el rebase.']),
        routeRisk('Curva cerrada','↪️','Te aproximas a una curva donde no puedes ver la salida.','rural',['Reducir antes de entrar y conservar carril',2,'Llegaste con velocidad controlada y trayectoria estable.'],['Frenar fuerte dentro de la curva',1,'Frenar tarde puede desestabilizar el vehículo.'],['Cortar la curva por el carril contrario',0,'Puedes encontrar tránsito sin posibilidad de evitarlo.']),
        routeRisk('Pendiente pronunciada','⛰️','Comienza un descenso largo y empinado.','rural',['Usar una marcha adecuada y freno motor',2,'Evitaste sobrecalentar los frenos.'],['Bajar en neutral para ahorrar combustible',0,'Pierdes control y retención del motor.'],['Mantener pisado el freno todo el descenso',1,'El uso continuo puede reducir su eficacia.']),
        routeRisk('Notificación del teléfono','📱','Tu teléfono vibra mientras circulas.','city',['Ignorarlo hasta estacionarte en un lugar seguro',2,'Mantienes atención visual, manual y mental.'],['Leerlo rápidamente sin responder',0,'Apartar la vista unos segundos basta para recorrer muchos metros.'],['Tomarlo solo cuando la calle esté recta',1,'Una vía recta también presenta riesgos inesperados.']),
        routeRisk('Señales de fatiga','🥱','Bostezas, parpadeas mucho y te cuesta mantener la trayectoria.','night',['Detenerte en un lugar seguro y descansar',2,'El descanso es la medida efectiva contra la fatiga.'],['Abrir la ventana y continuar',1,'El aire fresco solo produce alivio temporal.'],['Acelerar para llegar antes',0,'La velocidad agrava el riesgo cuando tu reacción está reducida.']),
        routeRisk('Persona entre autos','🧍','Ves piernas moviéndose entre vehículos estacionados.','city',['Reducir inmediatamente y cubrir el freno',2,'Reconociste una señal temprana de cruce oculto.'],['Mantenerte porque aún no sale',0,'Esperar a verla completa elimina tu margen.'],['Cambiar de carril sin revisar',1,'Puedes trasladar el riesgo a otra trayectoria.']),
        routeRisk('Entrada a glorieta','🔄','Hay vehículos circulando dentro de la glorieta.','city',['Ceder y entrar cuando exista espacio',2,'Respetaste a quienes ya circulan dentro.'],['Entrar porque vienes por la derecha',0,'La prioridad corresponde a la circulación interna señalizada.'],['Detenerte dentro para buscar salida',1,'Debes elegir y señalizar antes de bloquear la glorieta.']),
        routeRisk('Cruce ferroviario','🚆','Las luces del cruce comienzan a parpadear.','rural',['Detenerte antes de la barrera',2,'Un tren no puede frenar con rapidez ni esquivarte.'],['Cruzar antes de que baje por completo',0,'Calculas contra un vehículo de enorme distancia de frenado.'],['Detenerte sobre las vías para mirar',1,'Nunca debes quedar inmovilizado en la zona ferroviaria.']),
        routeRisk('Incorporación de carril','⇥','Otro carril termina y un vehículo necesita incorporarse.','highway',['Ajustar velocidad y facilitar un espacio',2,'Una incorporación previsible mantiene el flujo seguro.'],['Acelerar para cerrarle el paso',0,'Creas un conflicto lateral innecesario.'],['Circular junto a él sin decidir',1,'Mantener posiciones paralelas agota el espacio disponible.']),
        routeRisk('Vehículo averiado','🛠️','Un auto está detenido parcialmente dentro del carril.','highway',['Reducir, señalizar y cambiar con anticipación',2,'Hiciste visible y gradual tu maniobra.'],['Esquivarlo en el último instante',0,'Los demás no pueden anticipar tu cambio.'],['Detenerte detrás sin señalizar',1,'Puedes provocar una colisión por alcance.']),
        routeRisk('Intersección sin señal','✚','Llegas al mismo tiempo que otro vehículo a un cruce sin semáforo.','city',['Reducir, comunicarte y ceder si hay duda',2,'La prioridad nunca sustituye evitar una colisión.'],['Acelerar para establecer prioridad',0,'La velocidad convierte la duda en conflicto.'],['Entrar mirando solo hacia un lado',1,'Debes comprobar todas las trayectorias.']),
        routeRisk('Pasajero sin cinturón','🛡️','Una persona dentro del auto aún no se coloca el cinturón.','city',['Esperar a que todas las personas se aseguren',2,'La protección debe estar lista antes de iniciar.'],['Avanzar despacio mientras se lo pone',1,'Un impacto puede ocurrir incluso a baja velocidad.'],['Dejarlo a su elección',0,'Como conductor debes procurar un traslado seguro.']),
        routeRisk('Pérdida de presión','🛞','El volante vibra y el vehículo comienza a desviarse.','highway',['Sujetar firme, soltar acelerador y orillarte',2,'Reducir gradualmente conserva estabilidad.'],['Frenar de golpe',0,'Una frenada brusca puede hacerte perder control.'],['Girar rápido hacia la orilla',1,'El cambio repentino agrava la inestabilidad.']),
        routeRisk('Conductor agresivo','😠','Otro vehículo te sigue muy cerca y hace cambios bruscos.','highway',['Conservar calma y facilitar que se aleje',2,'Separarte del conflicto reduce la exposición.'],['Frenar para darle una lección',0,'Provocas deliberadamente una posible colisión.'],['Competir para no dejarlo pasar',1,'Aumentas velocidad y duración del conflicto.']),
    ];

    function actor(id, tag, label, detail, sprite, color, width, height, path) { return { id, tag, label, detail, sprite, color, width, height, path }; }
    function routeRisk(title,icon,prompt,theme,...choices){return{title,icon,prompt,theme,scene:routeScene(title),choices};}
    function routeScene(title){
        const scenes={
            'Zona escolar':'school','Balón en la calle':'ball','Paso peatonal':'crosswalk','Semáforo amarillo':'intersection','Semáforo en rojo':'intersection',
            'Vehículo de emergencia':'emergency','Ciclista adelante':'cyclist','Moto en el costado':'motorcycle','Autobús detenido':'bus','Puerta de automóvil':'door',
            'Lluvia intensa':'rain','Charco profundo':'puddle','Conducción nocturna':'night','Banco de niebla':'fog','Obras en la vía':'works','Animal en el camino':'animal',
            'Camión adelante':'truck','Curva cerrada':'curve','Pendiente pronunciada':'hill','Notificación del teléfono':'phone','Señales de fatiga':'fatigue',
            'Persona entre autos':'hidden-person','Entrada a glorieta':'roundabout','Cruce ferroviario':'railroad','Incorporación de carril':'merge','Vehículo averiado':'breakdown',
            'Intersección sin señal':'intersection','Pasajero sin cinturón':'seatbelt','Pérdida de presión':'tire','Conductor agresivo':'aggressive'
        };
        return scenes[title]||'generic';
    }
    function h(value) { return String(value).replace(/[&<>'"]/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c])); }
    function scrollGameTop(){const view=document.querySelector('[data-view="game"]');if(view)requestAnimationFrame(()=>view.scrollIntoView({block:'start',behavior:'auto'}));}
    function clamp(value, min, max) { return Math.max(min, Math.min(max, value)); }
    function smooth(value) { return value * value * (3 - 2 * value); }
    function motion(progress, start, end) { return smooth(clamp((progress - start) / (end - start), 0, 1)); }
    function seededShuffle(items, seed) { const out = items.slice(); let n = seed; for (let i = out.length - 1; i; i--) { n = (n * 9301 + 49297) % 233280; const j = Math.floor((n / 233280) * (i + 1)); [out[i], out[j]] = [out[j], out[i]]; } return out; }
    function getAudioContext(){
        if(!audioContext){const Audio=window.AudioContext||window.webkitAudioContext;if(!Audio)return null;audioContext=new Audio();}
        if(audioContext.state==='suspended')audioContext.resume().catch(()=>{});
        return audioContext;
    }
    function keepAudio(node){audioNodes.push(node);node.onended=()=>{audioNodes=audioNodes.filter(item=>item!==node);try{node.disconnect();}catch(_){}};}
    function stopSounds(){audioNodes.forEach(node=>{try{node.stop();}catch(_){ }try{node.disconnect();}catch(_){ }});audioNodes=[];}
    function soundTone(freq,start,duration,gain=.04,type='sine',endFreq=null){
        const ctx=getAudioContext();if(!ctx)return;const now=ctx.currentTime,osc=ctx.createOscillator(),volume=ctx.createGain();osc.type=type;osc.frequency.setValueAtTime(freq,now+start);if(endFreq)osc.frequency.exponentialRampToValueAtTime(endFreq,now+start+duration);volume.gain.setValueAtTime(.0001,now+start);volume.gain.exponentialRampToValueAtTime(gain,now+start+.025);volume.gain.exponentialRampToValueAtTime(.0001,now+start+duration);osc.connect(volume).connect(ctx.destination);osc.start(now+start);osc.stop(now+start+duration+.03);keepAudio(osc);
    }
    function soundNoise(start,duration,gain=.025,frequency=900){
        const ctx=getAudioContext();if(!ctx)return;const now=ctx.currentTime,length=Math.max(1,Math.floor(ctx.sampleRate*duration)),buffer=ctx.createBuffer(1,length,ctx.sampleRate),data=buffer.getChannelData(0);for(let i=0;i<length;i++)data[i]=(Math.random()*2-1)*(1-i/length);const source=ctx.createBufferSource(),filter=ctx.createBiquadFilter(),volume=ctx.createGain();source.buffer=buffer;filter.type='lowpass';filter.frequency.value=frequency;volume.gain.setValueAtTime(.0001,now+start);volume.gain.exponentialRampToValueAtTime(gain,now+start+.02);volume.gain.exponentialRampToValueAtTime(.0001,now+start+duration);source.connect(filter).connect(volume).connect(ctx.destination);source.start(now+start);source.stop(now+start+duration+.03);keepAudio(source);
    }
    function journeyDriveSound(risk){
        soundTone(58,0,1.82,.022,'sawtooth',76);soundTone(116,0,1.82,.009,'triangle',144);soundNoise(.02,1.78,.007,210);
        if(risk.theme==='rain')soundNoise(0,1.82,.018,2600);
    }
    function journeyPromptSound(risk){
        if(risk.scene==='railroad'){soundTone(740,0,.13,.045,'square');soundTone(740,.27,.13,.045,'square');soundNoise(0,.75,.012,170);return;}
        if(risk.scene==='emergency'){soundTone(620,0,.22,.035,'sine',880);soundTone(880,.24,.22,.035,'sine',620);return;}
        if(risk.scene==='aggressive'){soundTone(330,0,.12,.04,'square');soundTone(330,.17,.2,.04,'square');return;}
        if(risk.scene==='school'||risk.scene==='crosswalk'||risk.scene==='ball'){soundTone(660,0,.12,.035,'sine');soundTone(880,.16,.15,.035,'sine');return;}
        soundTone(520,0,.1,.025,'sine');
    }
    function journeyOutcomeSound(risk,safety){
        if(risk.scene==='railroad')soundNoise(0,1.35,.035,190);
        if(safety===2){soundTone(520,0,.14,.035,'sine');soundTone(660,.15,.16,.04,'sine');soundTone(820,.32,.2,.04,'sine');return;}
        if(safety===1){soundNoise(0,.42,.028,1500);soundTone(310,.32,.3,.038,'triangle',210);return;}
        soundNoise(0,.38,.045,1900);soundTone(145,.3,.22,.07,'sawtooth',72);soundNoise(.48,.42,.07,430);
    }
    function stopAnimation() { cancelAnimationFrame(animationFrame); clearTimeout(feedbackTimer); stopSounds(); animationFrame = 0; }
    function score(value) { app.score.textContent = `${value} pts`; }
    function asset(folder, file) { return `${app.assetsUrl}/${folder}/${file}`; }

    function open(config) {
        stopAnimation();
        app = config;
        if (config.type === 'hazard') initHazard();
        if (config.type === 'priority') initPriority();
        if (config.type === 'decision') initDecision();
        if (config.type === 'journey') initJourney();
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
            if (progress >= .46) { t = motion(progress,.46,.80); return { x:.655-.190*t, y:.590+.085*t, w:.085+.042*t, hh:.123+.060*t }; }
            t = motion(progress,.26,.78); const d=.036+.044*t; return { x:.716-.155*t, y:.640+.108*t, w:d, hh:d };
        }
        if (type === 'door') { t=motion(progress,.22,.66); return { x:(.681-.105*t+.700)/2, y:(.617-.015*t+.700+.006*t)/2, w:.700-(.681-.105*t), hh:(.700+.006*t)-(.617-.015*t) }; }
        if (type === 'pedestrian') { t=motion(progress,.32,.91); return { x:.148+.37*t, y:.604+.066*t, w:.113+.039*t, hh:.170+.041*t }; }
        if (type === 'motorcycle') { t=motion(progress,.36,.88); return { x:.686-.165*t, y:.607+.172*t, w:.097+.084*t, hh:.129+.105*t }; }
        if (type === 'bus') { t=motion(progress,.25,.78); return { x:(730.5-252*t)/960, y:(411.5+45*t)/720, w:(93+13*t)/960, hh:(147+18*t)/720 }; }
        if (type === 'driveway') { t=motion(progress,.20,.88); return { x:(899-285*t)/960, y:(425+98*t)/720, w:(206+27*t)/960, hh:(110+20*t)/720 }; }
        if (type === 'brake') { t=motion(progress,.30,.91); return { x:.5, y:(329+103*t)/720, w:(88+44*t)/960, hh:(116+58*t)/720 }; }
        t=motion(progress,.34,.88); return { x:(845-225*t)/960, y:(680-280*t)/720, w:(110-32*t)/960, hh:(145-42*t)/720 };
    }

    function hazardSprite(file, rect, className, folder) {
        return `<img class="mav-hazard-sprite ${className || ''}" src="${asset(folder || 'detecta_peligro',file)}" style="left:${(rect.x-rect.w/2)*100}%;top:${(rect.y-rect.hh/2)*100}%;width:${rect.w*100}%;height:${rect.hh*100}%" alt="">`;
    }

    function renderHazard() {
        stopAnimation();
        const scene = hazards[state.level];
        app.content.innerHTML = `<div class="mav-game mav-hazard-game">
            ${progressHeader('ESCENA', state.level, hazards.length, `${scene.stage === 2 ? 'ETAPA 2 · ' : ''}${state.streak >= 2 ? `🔥 COMBO x${state.streak} · ` : ''}${state.level+1} / ${hazards.length}`)}
            <div class="mav-game-heading"><h2>${h(scene.title)}</h2><p>${h(scene.hint)}</p></div>
            <div class="mav-hazard-wrap ${scene.landscape ? 'landscape' : ''}"><div class="mav-hazard-scene ${scene.landscape ? 'landscape' : ''}" data-mav-hazard-scene>
                <img class="mav-scene-bg" src="${asset(scene.folder || 'detecta_peligro',scene.image)}" alt="Escena vial: ${h(scene.title)}">
                <div data-mav-hazard-layers></div>
                <div class="mav-scene-shade"></div><div class="mav-scene-chip">◉ OBSERVA</div><div class="mav-countdown" data-mav-countdown>8 s</div>
                <div class="mav-timebar"><i data-mav-timebar></i></div>
            </div></div>
            <div class="mav-bottom-panel" data-mav-feedback>${state.resolved ? hazardResult(scene) : '<strong>☝ Toca el peligro en cuanto aparezca</strong>'}</div>
            ${state.tutorial ? tutorial('👁','MISIÓN: ANTICIPAR','Observa la calle.<br>Encuentra el peligro.','Toca el riesgo apenas lo identifiques. Ganas más puntos si te anticipas, y pierdes por falsas alarmas.','Cada escena dura 8 segundos.','data-mav-hazard-start','COMENZAR MISIÓN') : ''}
            ${state.stageBreak ? tutorial('Ⅱ','SEGUNDA ETAPA','Nuevos entornos, menos visibilidad','Ahora enfrentarás cruces ocultos, salidas de cochera, pavimento mojado y puntos ciegos con escenarios completamente distintos.','Conservas tus puntos y tu combo.','data-mav-stage-start','CONTINUAR RECORRIDO') : ''}
        </div>`;
        bindHazard();
        if (!state.tutorial && !state.stageBreak && !state.resolved) startHazardTimeline();
        if (state.resolved) drawHazardFrame();
    }

    function hazardResult(scene) {
        return `<div class="mav-result-copy ${state.hit ? 'good' : 'bad'}"><strong>${state.hit ? '✓' : '!'} ${h(state.feedback)}</strong><p>${h(scene.explanation)}</p><button type="button" class="mav-action" data-mav-hazard-next>${state.level + 1 === hazards.length ? 'Ver resultado' : 'Siguiente escena'}</button></div>`;
    }

    function bindHazard() {
        const start = app.content.querySelector('[data-mav-hazard-start]');
        if (start) start.onclick = () => { state.tutorial=false; renderHazard(); };
        const stageStart = app.content.querySelector('[data-mav-stage-start]');
        if (stageStart) stageStart.onclick = () => { state.stageBreak=false; renderHazard(); };
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
        if (scene.type === 'bus') {
            if (p >= .25) html += hazardSprite('peaton.png',hazardRect('bus',p),'','un_segundo_antes');
            html += hazardSprite('autobus.png',{x:715/960,y:432/720,w:208/960,hh:290/720},'','un_segundo_antes');
            html += hazardSprite('capo_auto.png',{x:481/960,y:713/720,w:326/960,hh:130/720},'','un_segundo_antes');
        }
        if (scene.type === 'driveway') {
            if (p >= .20) html += hazardSprite('auto_lateral.png',hazardRect('driveway',p),'mav-driveway-car','un_segundo_antes');
            html += hazardSprite('capo_auto.png',{x:480/960,y:713/720,w:326/960,hh:130/720},'','un_segundo_antes');
        }
        if (scene.type === 'brake') {
            if (p >= .30) html += hazardSprite('auto_frenando.png',hazardRect('brake',p),'','un_segundo_antes');
            html += hazardSprite('capo_auto.png',{x:481/960,y:711/720,w:326/960,hh:130/720},'','un_segundo_antes');
        }
        if (scene.type === 'blindspot') {
            if (p >= .34) html += hazardSprite('motocicleta.png',hazardRect('blindspot',p),'','un_segundo_antes');
            html += hazardSprite('capo_auto.png',{x:481/960,y:711/720,w:326/960,hh:130/720},'','un_segundo_antes');
        }
        if (scene.rain) html += '<canvas class="mav-rain-canvas" data-mav-rain aria-hidden="true"></canvas>';
        if (state.resolved) { const r=hazardRect(scene.type,p); html += `<span class="mav-risk-marker ${state.hit?'good':'bad'}" style="left:${r.x*100}%;top:${r.y*100}%">${state.hit?'✓':'!'}</span>`; }
        layers.innerHTML = html;
        if (scene.rain) drawRain(layers.querySelector('[data-mav-rain]'),p);
        const countdown=app.content.querySelector('[data-mav-countdown]'); if(countdown){ countdown.textContent=`${Math.ceil(8*(1-p))} s`; countdown.classList.toggle('urgent',p>.625&&!state.resolved); }
        const bar=app.content.querySelector('[data-mav-timebar]'); if(bar){ bar.style.width=`${(1-p)*100}%`; bar.className=state.resolved?(state.hit?'good':'bad'):(p>.72?'urgent':''); }
    }

    function drawRain(canvas, progress) {
        if (!canvas) return;
        const scene=canvas.closest('[data-mav-hazard-scene]'),bounds=scene.getBoundingClientRect(),dpr=Math.min(window.devicePixelRatio||1,2),w=Math.max(1,bounds.width),hh=Math.max(1,bounds.height);
        canvas.width=Math.round(w*dpr);canvas.height=Math.round(hh*dpr);
        const ctx=canvas.getContext('2d');ctx.scale(dpr,dpr);ctx.lineCap='round';
        for(let i=0;i<78;i++){
            const speed=.72+(i%7)*.055,phase=(i*97)%113/113;
            const y=((phase+progress*3.8*speed)%1.12)*hh-20;
            const drift=progress*34*speed;
            const x=((i*71+(i%5)*19-drift)%(w+70)+w+70)%(w+70)-35;
            const len=6+(i%5)*2.2,alpha=.24+(i%4)*.09;
            ctx.beginPath();ctx.moveTo(x,y);ctx.lineTo(x-2.2,y+len);ctx.strokeStyle=`rgba(220,244,253,${alpha})`;ctx.lineWidth=.9+(i%3)*.35;ctx.stroke();
        }
    }

    function onHazardTap(event) {
        if (state.resolved || state.tutorial || state.stageBreak) return;
        const scene = hazards[state.level];
        const bounds = event.currentTarget.getBoundingClientRect();
        const x=(event.clientX-bounds.left)/bounds.width, y=(event.clientY-bounds.top)/bounds.height;
        const p=state.progress, r=hazardRect(scene.type,p);
        let valid=p>=scene.revealAt && Math.abs(x-r.x)<=r.w/2+.03 && Math.abs(y-r.y)<=r.hh/2+.025;
        if(scene.type==='motorcycle' && r.x-r.w/2>=.628) valid=false;
        if(valid){
            stopAnimation(); const anticipation=clamp((scene.dangerAt-p)/(scene.dangerAt-scene.revealAt),0,1); const gained=180+Math.round(anticipation*320)+Math.min(state.streak,4)*60;
            if(scene.type==='bus') state.progress=Math.max(p,.82);
            if(scene.type==='child') state.progress=Math.max(p,.74);
            if(scene.type==='blindspot') state.progress=Math.max(p,.82);
            state.resolved=true; state.hit=true; state.hits++; state.streak++; state.score+=gained; state.feedback=p<=scene.idealAt?`¡Excelente anticipación! +${gained}`:anticipation>.35?`¡Buen ojo! +${gained}`:`¡Riesgo detectado! +${gained}`; score(state.score); renderHazard();
        } else {
            state.mistakes++; state.streak=0; state.score=Math.max(0,state.score-45); score(state.score);
            const panel=app.content.querySelector('[data-mav-feedback]'); if(panel) panel.innerHTML='<strong class="bad">⚠ Falsa alarma · −45 puntos</strong>';
            clearTimeout(feedbackTimer); feedbackTimer=setTimeout(()=>{const current=app.content.querySelector('[data-mav-feedback]'); if(current&&!state.resolved) current.innerHTML='<strong>☝ Toca el peligro en cuanto aparezca</strong>';},950);
        }
    }

    function timeoutHazard(){ if(state.resolved)return; state.mistakes++;state.streak=0;state.resolved=true;state.hit=false;state.feedback='¡El peligro te sorprendió!';renderHazard(); }
    function nextHazard(){ if(state.level+1===hazards.length){showResult('detecta_peligro','Detecta el peligro',state.score,state.hits,state.mistakes,state.hits===hazards.length&&state.mistakes===0?3:state.hits>=Math.ceil(hazards.length*.75)?2:1,'peligros detectados');return;} state.level++;state.stageBreak=state.level===4;state.resolved=false;state.hit=false;state.feedback='';state.progress=0;renderHazard();scrollGameTop(); }

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
    function priorityAction(){if(!state.checked){checkPriority();return;}if(!state.correct){state.checked=false;state.correct=false;state.progress=0;renderPriority();return;}if(state.level+1===puzzles.length){showResult('quien_pasa','¿Quién pasa primero?',state.score,state.solved,state.mistakes,state.solved===4&&state.mistakes===0?3:state.solved>=3?2:1,'cruces resueltos');return;}state.level++;state.checked=false;state.correct=false;state.progress=0;loadPuzzle();renderPriority();scrollGameTop();}
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
    function nextDecision(){if(state.level+1===decisions.length){showResult('segundo_antes','Un segundo antes',state.score,state.safe,state.mistakes,state.safe===4&&state.mistakes===0?3:state.safe>=3?2:1,'decisiones preventivas');return;}state.level++;state.choice=null;state.reviewing=false;state.alreadyScored=false;state.loop=true;makeChoiceOrder();renderDecision();scrollGameTop();}
    function spriteMarkup(name,x,y,w,hh,angle){return `<img class="mav-decision-sprite" src="${asset('un_segundo_antes',name)}" style="left:${x/9.6}%;top:${y/7.2}%;width:${w/9.6}%;height:${hh/7.2}%;transform:rotate(${angle||0}rad)" alt="">`;}
    function decisionObjects(scene,p,safety){
        const selected=safety!==null,t=smooth(p),advance=selected?t:0,preview=selected?0:p,risky=selected&&safety===0,medium=selected&&safety===1;
        let out='';
        if(scene.type==='bus'){
            out+=spriteMarkup('peaton.png',684-(selected?252*advance:65*preview),338+(selected?45*advance:8*preview),93+13*advance,147+18*advance);
            out+=spriteMarkup('autobus.png',611,287,208,290);
            out+=spriteMarkup('capo_auto.png',318+(risky?54*advance:medium?19*advance:0),648-(risky?78*advance:medium?27*advance:0),326,130);
        }
        if(scene.type==='driveway'){
            out+=spriteMarkup('auto_lateral.png',796-(selected?276*advance:35*preview),370+(selected?98*advance:13*preview),206+27*advance,110+20*advance,-.11);
            out+=spriteMarkup('capo_auto.png',317+(risky?30*advance:0),648-(risky?83*advance:medium?25*advance:0),326,130);
        }
        if(scene.type==='rain'){
            const grow=selected?(safety===2?18:safety===1?40:66)*advance:0,outW=88+grow,outH=116+grow*1.31;
            out+=spriteMarkup('auto_frenando.png',480-outW/2,271+(selected?(safety===2?34:safety===1?80:119)*advance:5*preview),outW,outH);
            out+=spriteMarkup('capo_auto.png',318,646-(risky?63*advance:medium?19*advance:0),326,130);
        }
        if(scene.type==='blindSpot'){
            const travel=selected?(safety===2?1:safety===1?.72:.5)*advance:.3*preview;
            const motoW=105-28*travel,motoH=140-38*travel;
            out+=spriteMarkup('motocicleta.png',815-205*travel,575-235*travel,motoW,motoH);
            out+=spriteMarkup('capo_auto.png',318+(risky?82*advance:medium?38*advance:0),646,326,130,selected ? (risky ? .045*advance : medium ? .018*advance : 0) : 0);
        }
        return out;
    }
    function startDecisionAnimation(){const scene=decisions[state.level],choice=state.choice===null?null:scene.choices[state.choice],safety=choice?choice[1]:null,start=performance.now(),duration=3400;const frame=now=>{state.progress=clamp((now-start)/duration,0,1);drawDecisionFrame(scene,safety);if(state.progress<1)animationFrame=requestAnimationFrame(frame);else if(state.loop){state.progress=0;startDecisionAnimation();}};animationFrame=requestAnimationFrame(frame);}
    function drawDecisionFrame(scene,safety){const objects=app.content.querySelector('[data-mav-decision-objects]');if(objects)objects.innerHTML=decisionObjects(scene,state.progress,safety);const bar=app.content.querySelector('[data-mav-decision-bar]');if(bar){bar.style.width=`${(safety===null?1-state.progress:state.progress)*100}%`;bar.className=safety===2?'good':safety===1?'medium':safety===0?'bad':'';}const canvas=app.content.querySelector('[data-mav-effects]');if(!canvas)return;const rect=canvas.getBoundingClientRect(),dpr=window.devicePixelRatio||1;canvas.width=rect.width*dpr;canvas.height=rect.height*dpr;const ctx=canvas.getContext('2d');ctx.scale(dpr*rect.width/960,dpr*rect.height/720);if(scene.type==='rain'){ctx.strokeStyle='rgba(190,226,240,.55)';ctx.lineWidth=1.35;ctx.lineCap='round';for(let i=0;i<68;i++){const x=(i*131+state.progress*36)%990-15,y=(i*87+state.progress*2680)%770-25;ctx.beginPath();ctx.moveTo(x,y);ctx.lineTo(x-2.5,y+11);ctx.stroke();}}if(safety!==null&&state.progress>.43){const reveal=clamp((state.progress-.43)/.4,0,1),em=Math.sin(reveal*Math.PI/2),centers={bus:[478,474],driveway:[630,516],rain:[480,470],blindSpot:[700,470]},center=centers[scene.type],color=safety===2?'22,134,90':safety===1?'255,166,45':'200,63,78',radius=64+24*em;ctx.beginPath();ctx.arc(center[0],center[1],radius,0,Math.PI*2);ctx.fillStyle=`rgba(${color},${.17*em})`;ctx.fill();ctx.beginPath();ctx.arc(center[0],center[1],radius*.82,0,Math.PI*2);ctx.strokeStyle=`rgba(${color},${.7*em})`;ctx.lineWidth=6;ctx.stroke();if(state.progress>=.74){ctx.fillStyle=`rgb(${color})`;ctx.font='900 61px sans-serif';ctx.textAlign='center';ctx.fillText(safety===2?'✓':'!',center[0],center[1]+22);}}}

    // Ruta segura ---------------------------------------------------------
    function initJourney(){
        app.title.textContent='Ruta segura';
        state={game:'journey',score:0,total:0,index:0,correct:0,mistakes:0,phase:'setup',choice:null,soundOn:true};
        score(0);renderJourneySetup();
    }

    function renderJourneySetup(){
        stopAnimation();
        app.content.innerHTML=`<div class="mav-route-setup">
            <div class="mav-route-setup-art"><div class="mav-mini-road"><i></i><img src="${asset('quien_pasa','auto_azul.png')}" alt=""></div><span>📍</span><b>DESTINO</b></div>
            <div class="mav-route-setup-copy"><span class="eyebrow">NUEVO RECORRIDO</span><h2>Ruta segura</h2><p>Conduce desde el punto de partida hasta tu destino. La ruta cambia en cada partida y presenta decisiones reales mientras el auto avanza.</p>
            <div class="mav-route-features"><span>↝ Vista aérea animada</span><span>⤨ 30 riesgos diferentes</span><span>◆ Decisiones con consecuencias</span></div>
            <h3>¿Qué tan largo será el recorrido?</h3><div class="mav-route-lengths">
                <button type="button" data-mav-route-length="10"><b>10</b><span>situaciones</span><small>Recorrido corto · 5–7 min</small></button>
                <button type="button" data-mav-route-length="20"><b>20</b><span>situaciones</span><small>Recorrido medio · 10–14 min</small></button>
                <button type="button" data-mav-route-length="30"><b>30</b><span>situaciones</span><small>Ruta completa · 15–20 min</small></button>
            </div></div>
        </div>`;
        app.content.querySelectorAll('[data-mav-route-length]').forEach(btn=>btn.onclick=()=>startJourney(Number(btn.dataset.mavRouteLength)));
    }

    function randomShuffle(items){
        const out=items.slice();
        for(let i=out.length-1;i>0;i--){const j=Math.floor(Math.random()*(i+1));[out[i],out[j]]=[out[j],out[i]];}
        return out;
    }

    function startJourney(total){
        state.total=total;state.deck=randomShuffle(journeyRisks).slice(0,total);state.index=0;state.correct=0;state.mistakes=0;state.score=0;state.phase='driving';state.choice=null;
        score(0);renderJourney();
    }

    function renderJourney(){
        stopAnimation();
        const risk=state.deck[state.index],selected=state.choice===null?null:risk.choices[state.choice],safety=selected?selected[1]:null,progress=Math.round((state.index/state.total)*100);
        app.content.innerHTML=`<div class="mav-route-game">
            <div class="mav-route-status"><div><span>PARTIDA</span><strong>Centro urbano</strong></div><div class="mav-route-meter"><i style="width:${progress}%"></i><b>${progress}% del recorrido</b></div><button type="button" class="mav-route-sound" data-mav-route-sound aria-label="${state.soundOn?'Silenciar':'Activar'} efectos de sonido" title="${state.soundOn?'Silenciar':'Activar'} efectos de sonido">${state.soundOn?'🔊':'🔇'}</button><div><span>DESTINO</span><strong>Zona segura</strong></div></div>
            <div class="mav-route-layout">
                <div class="mav-route-map theme-${risk.theme} scene-${risk.scene} phase-${state.phase} choice-${state.choice===null?'none':state.choice} ${safety===2?'result-safe':safety===1?'result-medium':safety===0?'result-danger':''}">
                    ${journeyMapBackground(risk)}
                    <div class="mav-route-side side-left"><i></i><b></b><em></em></div><div class="mav-route-side side-right"><i></i><b></b><em></em></div>
                    <div class="mav-route-road"><span class="mav-route-line line-a"></span><span class="mav-route-line line-b"></span><span class="mav-route-line line-c"></span>
                        ${journeySceneMarkup(risk)}
                        <img class="mav-route-car" src="${asset('quien_pasa','auto_azul.png')}" alt="Tu automóvil">
                        <div class="mav-route-signal">${state.phase==='outcome'?(safety===2?'✓':safety===1?'⚠':'!'):''}</div>
                    </div>
                    <span class="mav-route-zone">${routeZoneName(risk.theme)}</span><span class="mav-route-counter">RIESGO ${state.index+1} / ${state.total}</span>
                </div>
                <div class="mav-route-panel">${journeyPanel(risk,selected,safety)}</div>
            </div>
        </div>`;
        bindJourney();
        if(state.soundOn){if(state.phase==='driving')journeyDriveSound(risk);else if(state.phase==='question')journeyPromptSound(risk);else if(state.phase==='outcome')journeyOutcomeSound(risk,safety);}
        if(state.phase==='driving') feedbackTimer=setTimeout(()=>{state.phase='question';renderJourney();},1900);
    }

    function routeZoneName(theme){return({school:'ZONA ESCOLAR',city:'AVENIDA URBANA',emergency:'VÍA PRIORITARIA',park:'ZONA RECREATIVA',rain:'PAVIMENTO MOJADO',night:'TRAMO NOCTURNO',fog:'VISIBILIDAD REDUCIDA',works:'OBRAS',highway:'VÍA RÁPIDA',rural:'TRAMO DE CARRETERA'}[theme]||'RECORRIDO URBANO');}

    function journeyMapBackground(risk){
        if(risk.scene==='intersection')return `<img class="mav-route-scene-bg" src="${asset('quien_pasa','escena_1.png')}" alt="Intersección urbana vista desde arriba">`;
        if(risk.scene==='roundabout')return `<img class="mav-route-scene-bg" src="${asset('quien_pasa','escena_2.png')}" alt="Glorieta urbana vista desde arriba">`;
        if(risk.scene==='curve')return `<img class="mav-route-scene-bg" src="${asset('ruta_segura','curva_rural.png')}" alt="Carretera rural con curva vista desde arriba">`;
        return '';
    }

    function journeySceneMarkup(risk){
        const label=`<b class="mav-route-event-label">${h(risk.title)}</b>`;
        if(risk.scene==='breakdown')return `<div class="mav-route-prop prop-breakdown"><img src="${asset('quien_pasa','auto_amarillo.png')}" alt="Vehículo averiado"><span>⚠</span>${label}</div><img class="mav-route-rival" src="${asset('quien_pasa','auto_rojo.png')}" alt="Vehículo en el carril contiguo"><div class="mav-route-impact">✦</div>`;
        if(risk.scene==='intersection')return `<div class="mav-cross-street"></div><div class="mav-traffic-light">${risk.title.includes('rojo')?'🔴':risk.title.includes('amarillo')?'🟡':'⚠'}</div><img class="mav-route-cross-car" src="${asset('quien_pasa','auto_amarillo.png')}" alt="Vehículo que cruza">${label}<div class="mav-route-impact">✦</div>`;
        if(risk.scene==='crosswalk'||risk.scene==='school'||risk.scene==='hidden-person')return `<div class="mav-route-crosswalk"></div><div class="mav-route-person">${risk.scene==='school'?'🧒':'🚶'}</div>${label}<div class="mav-route-impact">✦</div>`;
        if(risk.scene==='ball')return `<div class="mav-route-ball">⚽</div><div class="mav-route-person hidden-child">🧒</div>${label}<div class="mav-route-impact">✦</div>`;
        if(risk.scene==='roundabout')return `<img class="mav-route-cross-car" src="${asset('quien_pasa','auto_amarillo.png')}" alt="Vehículo dentro de la glorieta">${label}<div class="mav-route-impact">✦</div>`;
        if(risk.scene==='railroad')return `<div class="mav-route-rails"><i></i><b></b></div><div class="mav-route-barrier"></div><img class="mav-route-train" src="${asset('ruta_segura','tren_cenital.png')}" alt="Tren cruzando la carretera">${label}<div class="mav-route-impact">✦</div>`;
        if(risk.scene==='emergency')return `<img class="mav-route-special prop-emergency" src="${asset('quien_pasa','ambulancia.png')}" alt="Ambulancia">${label}<div class="mav-route-impact">✦</div>`;
        if(risk.scene==='cyclist')return `<img class="mav-route-special prop-cycle" src="${asset('quien_pasa','bici.png')}" alt="Bicicleta">${label}<div class="mav-route-impact">✦</div>`;
        if(risk.scene==='motorcycle')return `<img class="mav-route-special prop-motorcycle" src="${asset('quien_pasa','moto.png')}" alt="Motocicleta">${label}<div class="mav-route-impact">✦</div>`;
        if(risk.scene==='truck')return `<img class="mav-route-heavy prop-truck" src="${asset('ruta_segura','camion_cenital.png')}" alt="Camión de carga delante">${label}<img class="mav-route-oncoming" src="${asset('quien_pasa','auto_rojo.png')}" alt="Vehículo en sentido contrario"><div class="mav-route-impact">✦</div>`;
        if(risk.scene==='bus')return `<img class="mav-route-heavy prop-bus" src="${asset('un_segundo_antes','autobus.png')}" alt="Autobús detenido">${label}<div class="mav-route-impact">✦</div>`;
        if(risk.scene==='works')return `<div class="mav-route-cones">▲ ▲ ▲</div>${label}<div class="mav-route-impact">✦</div>`;
        if(risk.scene==='animal')return `<div class="mav-route-animal">🐕</div>${label}<div class="mav-route-impact">✦</div>`;
        if(risk.scene==='puddle')return `<div class="mav-route-puddle">≈</div>${label}<div class="mav-route-impact">✦</div>`;
        if(risk.scene==='door')return `<img class="mav-route-parked" src="${asset('quien_pasa','auto_amarillo.png')}" alt="Automóvil estacionado"><div class="mav-route-door">◢</div>${label}<div class="mav-route-impact">✦</div>`;
        if(risk.scene==='merge')return `<div class="mav-route-merge">⇥</div><img class="mav-route-rival merge-car" src="${asset('quien_pasa','auto_rojo.png')}" alt="Vehículo incorporándose">${label}<div class="mav-route-impact">✦</div>`;
        if(risk.scene==='aggressive')return `<img class="mav-route-rival aggressive-car" src="${asset('quien_pasa','auto_rojo.png')}" alt="Vehículo que sigue de cerca">${label}<div class="mav-route-impact">✦</div>`;
        if(risk.scene==='curve')return `<div class="mav-route-curve-sign">↪</div><img class="mav-route-oncoming curve-oncoming" src="${asset('quien_pasa','auto_rojo.png')}" alt="Vehículo en sentido contrario">${label}<div class="mav-route-impact">✦</div>`;
        if(risk.scene==='rain')return `<div class="mav-route-event"><span>${risk.icon}</span>${label}</div><img class="mav-route-rival weather-rival" src="${asset('quien_pasa','auto_amarillo.png')}" alt="Vehículo adelante bajo la lluvia"><div class="mav-route-impact">✦</div>`;
        if(risk.scene==='fog')return `<div class="mav-route-event"><span>${risk.icon}</span>${label}</div><img class="mav-route-rival weather-rival" src="${asset('quien_pasa','auto_amarillo.png')}" alt="Vehículo apenas visible dentro de la niebla"><div class="mav-route-impact">✦</div>`;
        if(risk.scene==='night')return `<div class="mav-route-event"><span>${risk.icon}</span>${label}</div><img class="mav-route-oncoming night-oncoming" src="${asset('quien_pasa','auto_rojo.png')}" alt="Vehículo en sentido contrario"><div class="mav-route-impact">✦</div>`;
        if(risk.scene==='seatbelt')return `<div class="mav-route-event seatbelt-event"><span>🧍</span>${label}</div><div class="mav-route-seatbelt">╲</div><div class="mav-route-impact passenger-impact">✦</div>`;
        if(['tire','fatigue','hill'].includes(risk.scene))return `<div class="mav-route-event"><span>${risk.icon}</span>${label}</div><div class="mav-route-crash-tree">🌳</div><div class="mav-route-impact">✦</div>`;
        if(risk.scene==='phone')return `<div class="mav-route-event"><span>${risk.icon}</span>${label}</div><img class="mav-route-rival phone-rival" src="${asset('quien_pasa','auto_rojo.png')}" alt="Vehículo en el carril contiguo"><div class="mav-route-impact">✦</div>`;
        return `<div class="mav-route-event"><span>${risk.icon}</span>${label}</div>`;
    }

    function journeyPanel(risk,selected,safety){
        if(state.phase==='driving')return `<div class="mav-route-driving-copy"><span>◆ EN RUTA</span><h2>Avanzando al siguiente punto…</h2><p>Mantén la atención. La próxima situación puede aparecer en cualquier momento.</p><div class="mav-route-loading"><i></i><i></i><i></i></div></div>`;
        if(state.phase==='question')return `<span class="mav-route-kicker">DECISIÓN ${state.index+1}</span><h2>${h(risk.title)}</h2><p>${h(risk.prompt)}</p><h3>¿Qué haces?</h3><div class="mav-route-choices">${risk.choices.map((choice,i)=>`<button type="button" data-mav-route-choice="${i}"><b>${String.fromCharCode(65+i)}</b><span>${h(choice[0])}</span></button>`).join('')}</div>`;
        const title=safety===2?'Decisión preventiva':safety===1?'Decisión incompleta':'Maniobra peligrosa';
        return `<div class="mav-route-outcome safety-${safety}"><i>${safety===2?'✓':safety===1?'⚠':'!'}</i><span>${safety===2?'+300':safety===1?'+120':'+0'} PUNTOS</span><h2>${title}</h2><p>${h(selected[2])}</p></div><button type="button" class="mav-action" data-mav-route-next>${state.index+1===state.total?'Llegar al destino':'Continuar la ruta'} →</button>`;
    }

    function bindJourney(){
        app.content.querySelectorAll('[data-mav-route-choice]').forEach(btn=>btn.onclick=()=>chooseJourney(Number(btn.dataset.mavRouteChoice)));
        const next=app.content.querySelector('[data-mav-route-next]');if(next)next.onclick=nextJourney;
        const sound=app.content.querySelector('[data-mav-route-sound]');if(sound)sound.onclick=()=>{state.soundOn=!state.soundOn;stopSounds();sound.textContent=state.soundOn?'🔊':'🔇';sound.setAttribute('aria-label',`${state.soundOn?'Silenciar':'Activar'} efectos de sonido`);sound.title=`${state.soundOn?'Silenciar':'Activar'} efectos de sonido`;if(state.soundOn){const risk=state.deck[state.index],selected=state.choice===null?null:risk.choices[state.choice],safety=selected?selected[1]:null;if(state.phase==='driving')journeyDriveSound(risk);else if(state.phase==='question')journeyPromptSound(risk);else journeyOutcomeSound(risk,safety);}};
    }

    function chooseJourney(index){
        if(state.phase!=='question')return;
        const safety=state.deck[state.index].choices[index][1];state.choice=index;state.phase='outcome';
        if(safety===2){state.correct++;state.score+=300;}else if(safety===1){state.mistakes++;state.score+=120;}else state.mistakes++;
        score(state.score);renderJourney();
    }

    function nextJourney(){
        if(state.index+1===state.total){const ratio=state.correct/state.total;showResult('ruta_segura','Ruta segura',state.score,state.correct,state.mistakes,ratio===1&&state.mistakes===0?3:ratio>=.75?2:1,'decisiones preventivas');return;}
        state.index++;state.choice=null;state.phase='driving';renderJourney();scrollGameTop();
    }

    function showResult(id,title,total,correct,mistakes,stars,label){stopAnimation();app.saveProgress(`game_${id}`,total);app.content.innerHTML=`<div class="mav-game-result"><div class="mav-stars">${[1,2,3].map(n=>`<span class="${n<=stars?'on':''}">★</span>`).join('')}</div><span>MISIÓN COMPLETADA</span><h2>${h(title)}</h2><strong>${total} puntos</strong><div class="mav-result-stats"><div><b>${correct}</b><small>${h(label)}</small></div><div><b>${mistakes}</b><small>errores</small></div><div><b>${stars}/3</b><small>estrellas</small></div></div><button type="button" class="mav-action" data-mav-replay>Jugar otra vez</button><button type="button" class="mav-secondary" data-home>Volver a los juegos</button></div>`;app.content.querySelector('[data-mav-replay]').onclick=()=>open({...app,type:state.game});}

    window.MAVGames = { open, stop: stopAnimation };
})();

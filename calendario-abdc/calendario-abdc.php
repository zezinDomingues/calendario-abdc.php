<?php
/**
 * Plugin Name:       Painel ABDC (Stats + Calendário + Cursos)
 * Description:       Exibe os cards de stats, lista de cursos e o calendário de eventos. 
 * Version:           2.12 
 * Author:            José Domingues / 2WP
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Acesso direto bloqueado
}

/**
 * Registra o shortcode [calendario_eventos_abdc]
 */
function abdc_registrar_shortcode_calendario() {
    add_shortcode( 'calendario_eventos_abdc', 'abdc_renderizar_calendario_shortcode' );
}
add_action( 'init', 'abdc_registrar_shortcode_calendario' );

/**
 * Função para carregar os scripts e estilos
 */
function abdc_carregar_scripts_estilos() {
    // 1. Carregar os scripts do FullCalendar
    wp_enqueue_script( 
        'fullcalendar', 
        'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js', 
        [], '6.1.11', true
    );
    
    // 2. Carregar a biblioteca Chart.js
    wp_enqueue_script(
        'chart-js',
        'https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js',
        [], '4.4.3', true
    );

    // 3. NOVO: Carregar a fonte Lexend Deca do Google Fonts
    wp_enqueue_style(
        'google-font-lexend-deca',
        'https://fonts.googleapis.com/css2?family=Lexend+Deca:wght@400;600&display=swap',
        [], null // null = sem número de versão
    );
}
add_action( 'wp_enqueue_scripts', 'abdc_carregar_scripts_estilos' );


/**
 * Função principal que gera o HTML do shortcode.
 */
function abdc_renderizar_calendario_shortcode() {

    // --- BUSCA 1: EVENTOS PARA O CALENDÁRIO (JSON) ---
    
    $eventos_formatados_json = []; 
    $args_calendario = array(
        'post_type'      => 'eventos',
        'posts_per_page' => -1,
        'post_status'    => ['publish', 'future'],
    );
    $query_calendario = new WP_Query( $args_calendario );

    if ( $query_calendario->have_posts() ) {
        while ( $query_calendario->have_posts() ) {
            $query_calendario->the_post();
            $data_inicio_raw = get_post_meta( get_the_ID(), 'data', true );
            if ( ! empty( $data_inicio_raw ) ) {
                $data_termino_raw = get_post_meta( get_the_ID(), 'data_termino', true );
                $link_evento      = get_post_meta( get_the_ID(), 'link_do_evento', true );
                $descricao_evento = get_post_meta( get_the_ID(), 'descricao', true );
                $url_final = ! empty( $link_evento ) ? $link_evento : get_the_permalink();
                $evento = [
                    'title' => get_the_title(),
                    'start' => $data_inicio_raw,
                    'url'   => $url_final,
                    'extendedProps' => [ 'description' => wp_strip_all_tags( $descricao_evento ) ]
                ];
                if ( ! empty( $data_termino_raw ) ) { $evento['end'] = $data_termino_raw; }
                $eventos_formatados_json[] = $evento;
            }
        }
    }
    wp_reset_postdata(); 
    $eventos_json = wp_json_encode( $eventos_formatados_json );
    
    
    // --- BUSCA 2: LISTA DE CURSOS (PARA A LISTA HTML) ---
    
    $html_lista_cursos = '';
    $args_lista = array(
        'post_type'      => 'sfwd-courses',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'title',
        'order'          => 'ASC',
    );

    $query_lista_cursos = new WP_Query( $args_lista );
    
    if ( $query_lista_cursos->have_posts() ) {
        $html_lista_cursos .= '<ul class="abdc-lista-cursos">'; 
        while ( $query_lista_cursos->have_posts() ) {
            $query_lista_cursos->the_post();
            
            $descricao_curso = get_the_content();
            $descricao_curta = wp_trim_words( $descricao_curso, 20, '...' );

            $html_lista_cursos .= sprintf(
                '<li><a href="%s" target="_blank" rel="noopener">
                    <div class="abdc-curso-titulo">%s</div>
                    <div class="abdc-curso-descricao">%s</div>
                </a></li>',
                esc_url( get_the_permalink() ),
                esc_html( get_the_title() ),
                esc_html( $descricao_curta )
            );
        }
        $html_lista_cursos .= '</ul>';
    } else {
        $html_lista_cursos = '<p class="abdc-sem-itens">Não há cursos disponíveis no momento.</p>';
    }
    wp_reset_postdata();
    
    // --- FIM DA BUSCA 2 ---
    

    
    // --- Início da renderização do HTML ---
    ob_start(); 
    ?>
    
    <div id="abdc-container-stats">

        <div class="abdc-stat-box">
            <div class="abdc-chart-wrapper">
                <canvas id="abdc-voucher-chart"></canvas>
                <div class="abdc-chart-text">80%</div>
            </div>
            <div class="abdc-stat-info">
                <h4>Vouchers usados</h4>
                <p>8 de 10</p>
            </div>
        </div>
        
        <div class="abdc-stat-box">
            <div class="abdc-stat-icon icon-eventos">
                <img src="https://deeppink-bear-752667.hostingersite.com/wp-content/uploads/2025/11/image-removebg-preview-2.png" alt="Ícone de Eventos">
            </div>
            <div class="abdc-stat-info">
                <h4>Eventos ativos</h4>
                <p>3</p>
            </div>
        </div>
        
        <div class="abdc-stat-box">
            <div class="abdc-stat-icon icon-assinaturas">
                <img src="https://deeppink-bear-752667.hostingersite.com/wp-content/uploads/2025/11/image-removebg-preview.png" alt="Ícone de Assinaturas">
            </div>
            <div class="abdc-stat-info">
                <h4>Assinaturas ativas</h4>
                <p>5</p>
            </div>
        </div>
        
        <div class="abdc-stat-box">
            <div class="abdc-stat-icon icon-pagamento">
                <img src="https://deeppink-bear-752667.hostingersite.com/wp-content/uploads/2025/11/image-removebg-preview-1.png" alt="Ícone de Pagamento">
            </div>
            <div class="abdc-stat-info">
                <h4>Próximo pagamento</h4>
                <p>25/04/2024</p>
            </div>
        </div>
        
    </div>

    <div id="abdc-container-inferior">
    
        <div id="abdc-novo-bloco">
            <h3>Cursos</h3>
            <?php echo $html_lista_cursos; ?>
        </div>
    
        <div id="abdc-container-calendario">
            <div id="abdc-calendario"></div>
        </div>
        
    </div> <div id="abdc-janela-evento" class="abdc-janela">
        <div class="abdc-janela-conteudo">
            <span class="abdc-janela-fechar">&times;</span>
            <h2 id="abdc-janela-titulo"></h2>
            <p id="abdc-janela-descricao"></p>
            <a href="#" id="abdc-janela-link" class="abdc-janela-botao" target="_blank" rel="noopener">Ver Evento</a>
        </div>
    </div>


    <style>
        /* --- Estilo dos Stats (Caixas Superiores) --- */
        #abdc-container-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr); 
            gap: 20px;
        }
        .abdc-stat-box {
            background-color: #0F1A3A; 
            padding: 20px;
            border-radius: 12px;
            color: #ffffff;
            display: flex;
            align-items: center; 
            gap: 15px; 
            font-family: "Lexend Deca", Sans-serif; /* <-- FONTE ATUALIZADA */
        }
        
        /* --- ESTILOS DO GRÁFICO --- */
        .abdc-chart-wrapper {
            position: relative;
            width: 110px;
            height: 110px;
            flex-shrink: 0; 
        }
        .abdc-chart-wrapper canvas {
            width: 100% !important;
            height: 100% !important;
        }
        .abdc-chart-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%); 
            color: #ffffff;
            font-size: 1.3em; 
            font-weight: 600;
        }
        
        .abdc-stat-icon {
            width: 50px; height: 50px;
            border-radius: 50%; 
            display: grid; place-items: center; 
            flex-shrink: 0; 
            overflow: hidden; 
        }
        .abdc-stat-icon img {
            width: 100%; height: 100%;
            object-fit: cover; 
        }
        
        .abdc-stat-icon.icon-eventos { background-color: #FFAB00; }
        .abdc-stat-icon.icon-assinaturas { background-color: #00C853; }
        .abdc-stat-icon.icon-pagamento { background-color: #1DE9B6; }
        
        .abdc-stat-info h4 {
            margin: 0 0 5px 0; font-size: 0.9em;
            color: #BDC5E2; font-weight: 400;
        }
        .abdc-stat-info p {
            margin: 0; font-size: 1.6em; 
            font-weight: 600; color: #ffffff;
        }

        /* --- Container Inferior (2 Colunas) --- */
        #abdc-container-inferior {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 25px; 
            align-items: start; 
        }
        
        /* --- Estilo do Bloco de Cursos --- */
        #abdc-novo-bloco {
            background-color: #ffffff; 
            border-radius: 12px; 
            padding: 20px;
            font-family: "Lexend Deca", Sans-serif; /* <-- FONTE ATUALIZADA */
            color: #333333;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            height: 100%;
        }
        #abdc-novo-bloco h3 {
            margin-top: 0;
            color: #111;
            font-size: 1.75em;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .abdc-lista-cursos {
            list-style: none; padding: 0; margin: 0;
            display: flex; flex-direction: column; gap: 10px;
        }
        .abdc-lista-cursos li a {
            display: block; padding: 15px;
            background-color: #f4f4f4;
            border-radius: 8px;
            text-decoration: none;
            transition: background-color 0.2s, color 0.2s;
            border-left: 5px solid #0F1A3A;
        }
        .abdc-lista-cursos li a:hover {
            background-color: #0F1A3A;
            border-left-color: #2962FF;
        }
        .abdc-sem-itens { color: #666; font-style: italic; }
        .abdc-curso-titulo {
            color: #333; font-weight: 600; font-size: 1.1em;
        }
        .abdc-curso-descricao {
            color: #666; font-size: 0.9em;
            line-height: 1.5; margin-top: 8px;
        }
        .abdc-lista-cursos li a:hover .abdc-curso-titulo { color: #ffffff; }
        .abdc-lista-cursos li a:hover .abdc-curso-descricao { color: #e0e0e0; }


        /* --- Estilo do Calendário (TEMA CLARO) --- */
        #abdc-container-calendario {
            max-width: 100%; 
            background-color: #ffffff; 
            border-radius: 12px; overflow: hidden; padding: 20px;
            font-family: "Lexend Deca", Sans-serif; /* <-- FONTE ATUALIZADA */
            color: #333333;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); 
            height: 100%; 
        }
        #abdc-calendario .fc-header-toolbar { margin-bottom: 1.5em; color: #111; }
        #abdc-calendario .fc-toolbar-title { font-size: 1.75em; font-weight: 600; color: #111; }
        #abdc-calendario .fc-button { 
            background: #f4f4f4; border: 1px solid #ddd; color: #333;
            box-shadow: none; border-radius: 6px; font-weight: 600; text-transform: capitalize; 
        }
        #abdc-calendario .fc-button:hover { background: #e9e9e9; }
        #abdc-calendario .fc-button:focus, #abdc-calendario .fc-button:active { box-shadow: none; background: #e0e0e0; }
        #abdc-calendario .fc-button-primary:not(:disabled).fc-button-active, 
        #abdc-calendario .fc-button-primary:not(:disabled):active { background-color: #e0e0e0; }
        #abdc-calendario .fc-col-header-cell { border: none; color: #666; font-weight: 500; }
        #abdc-calendario .fc-col-header-cell-cushion { text-decoration: none; padding: 10px 0; font-size: 0.9em; }
        #abdc-calendario .fc-daygrid-day { border: 1px solid #f0f0f0; background: none; }
        #abdc-calendario .fc-daygrid-day-number { color: #333; padding: 8px; font-size: 1em; font-weight: 500; }
        #abdc-calendario .fc-day-today { background-color: #fcf8e3; }
        #abdc-calendario .fc-day-today .fc-daygrid-day-number { color: #8a6d3b; font-weight: 700; }
        #abdc-calendario .fc-day-other .fc-daygrid-day-top { opacity: 0.3; }
        #abdc-calendario .fc-event { 
            background-color: #3498db; border: none; color: #ffffff;
            border-radius: 6px; padding: 5px 8px; margin: 1px 4px;
            font-size: 0.9em; font-weight: 600; cursor: pointer; 
        }
        #abdc-calendario .fc-event-title { color: #ffffff; white-space: normal; }
        #abdc-calendario .fc-scroller { overflow: visible !important; height: auto !important; }
        #abdc-calendario .fc-view-harness { height: auto !important; }

        /* --- Estilo do Modal (Pop-up) --- */
        .abdc-janela {
            display: none; position: fixed; z-index: 1000;
            left: 0; top: 0; width: 100%; height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            animation: fadeIn 0.3s;
        }
        .abdc-janela-conteudo {
            background-color: #2a2a4e; color: #e0e0e0;
            margin: 15% auto; padding: 25px 30px; border-radius: 12px;
            width: 90%; max-width: 500px;
            position: relative; animation: slideIn 0.3s;
            font-family: "Lexend Deca", Sans-serif; /* <-- FONTE ATUALIZADA */
        }
        .abdc-janela-fechar {
            color: #aaa; float: right; font-size: 28px;
            font-weight: bold; cursor: pointer;
        }
        .abdc-janela-fechar:hover,
        .abdc-janela-fechar:focus { color: #fff; }
        .abdc-janela-conteudo h2 { margin-top: 0; color: #ffffff; }
        .abdc-janela-conteudo p { margin-bottom: 25px; line-height: 1.6; }
        .abdc-janela-botao {
            background-color: #3498db; color: #ffffff;
            padding: 10px 20px; border-radius: 6px;
            text-decoration: none; font-weight: 600;
            transition: background-color 0.2s;
        }
        .abdc-janela-botao:hover { background-color: #2980b9; }

        @keyframes fadeIn { from {opacity: 0;} to {opacity: 1;} }
        @keyframes slideIn { from {transform: translateY(-50px);} to {transform: translateY(0);} }
        
        /* --- CSS Responsivo --- */
        @media (max-width: 960px) {
            #abdc-container-stats {
                grid-template-columns: repeat(2, 1fr);
            }
            #abdc-container-inferior {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 500px) {
            #abdc-container-stats {
                grid-template-columns: 1fr; 
            }
        }
        
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // --- 1. SELETORES DO CALENDÁRIO E MODAL ---
        var elementoCalendario = document.getElementById('abdc-calendario');
        var janelaModal      = document.getElementById('abdc-janela-evento');
        var tituloModal      = document.getElementById('abdc-janela-titulo');
        var descricaoModal   = document.getElementById('abdc-janela-descricao');
        var linkModal        = document.getElementById('abdc-janela-link');
        var botaoFecharModal  = document.getElementsByClassName('abdc-janela-fechar')[0];
        var dadosDosEventos = <?php echo $eventos_json; ?>;

        // --- 2. INICIAR CALENDÁRIO ---
        var calendario = new FullCalendar.Calendar(elementoCalendario, {
            initialView: 'dayGridMonth',
            locale: 'pt-br',
            height: 'auto',
            headerToolbar: {
                left: 'title',
                center: '',
                right: 'today prev,next'
            },
            buttonText: { today: 'Hoje' },
            events: dadosDosEventos,

            eventClick: function(info) {
                info.jsEvent.preventDefault();
                tituloModal.textContent = info.event.title;
                var desc = info.event.extendedProps.description;
                
                if (desc && desc.trim() !== '') {
                    descricaoModal.textContent = desc;
                } else {
                    descricaoModal.textContent = 'Não há descrição disponível para este evento.';
                }
                linkModal.href = info.event.url;
                janelaModal.style.display = 'block';
            }
        });
        calendario.render();
        
        // Funções para fechar o modal
        botaoFecharModal.onclick = function() {
            janelaModal.style.display = 'none';
        }
        window.onclick = function(evento) {
            if (evento.target == janelaModal) {
                janelaModal.style.display = 'none';
            }
        }
        
        // --- 3. INICIAR GRÁFICO DE VOUCHERS ---
        var ctxVoucher = document.getElementById('abdc-voucher-chart');
        if (ctxVoucher) {
            new Chart(ctxVoucher, {
                type: 'doughnut', // Tipo "rosca"
                data: {
                    datasets: [{
                        data: [8, 2], // 8 usados, 2 restantes (de 10)
                        backgroundColor: [
                            '#2962FF', // Cor dos "usados" (azul do seu design)
                            '#2a2a4e'  // Cor dos "restantes" (um azul escuro)
                        ],
                        borderWidth: 0, // Sem borda
                        hoverBorderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '75%', // O tamanho do "buraco" no meio
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: false }
                    }
                }
            });
        }
    });
    </script>

    <?php
    return ob_get_clean(); 
}
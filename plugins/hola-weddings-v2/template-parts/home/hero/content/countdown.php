<div class="hero-countdown mt-5 mt-md-3 <?= isset($wrapper_classes) ? $wrapper_classes : '' ?>">
    <div class="hw-hero-countdown simply-countdown notraslate <?= isset($skin) ? $skin : 'skin-light' ?>" data-no-dynamic-translation="true"></div>
    <script type="text/javascript">
        jQuery(function($){

            // Localized labels
            const LABELS = {
                en: {
                days:   { root: 'day',    lambda: (r, n) => (n !== 1 ? r + 's' : r) },
                hours:  { root: 'hour',   lambda: (r, n) => (n !== 1 ? r + 's' : r) },
                minutes:{ root: 'minute', lambda: (r, n) => (n !== 1 ? r + 's' : r) },
                seconds:{ root: 'second', lambda: (r, n) => (n !== 1 ? r + 's' : r) },
                },
                es: {
                days:   { root: 'día',    lambda: (r, n) => (n !== 1 ? 'días'   : 'día') },
                hours:  { root: 'hora',   lambda: (r, n) => (n !== 1 ? 'horas'  : 'hora') },
                minutes:{ root: 'minuto', lambda: (r, n) => (n !== 1 ? 'minutos': 'minuto') },
                seconds:{ root: 'segundo',lambda: (r, n) => (n !== 1 ? 'segundos':'segundo') },
                },
            };

            const langCode = getLang();

            simplyCountdown(".hw-hero-countdown", {
                year: <?= $date->format('Y') ?>,
                month: <?= $date->format('n') ?>,
                day: <?= $date->format('j') ?>,
                words: LABELS[langCode] || LABELS.en,
            });
        });
    </script>
</div>
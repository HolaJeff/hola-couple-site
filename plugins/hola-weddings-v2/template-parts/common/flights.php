<div class="flights">
    <div class="flight">
        <div class="mb-3 text-center"><strong><?= __('Arrival Flight', 'hola-weddings') ?><br><?= get_field('arrival_flight_number', $id)  ?></strong></div>
        <div class="details-separator position-relative"></div>
        <div class="arrival-flight position-relative d-flex">
            <div class="departure stop">
                <strong class="airport"><i class="fas fa-plane-departure mr-2"></i><?= get_field('arrival_departure_airport', $id) ?></strong>
                <div class="date"><?= (new \DateTime(get_field('arrival_departure_date_time', $id)))->format('F, j Y H:m'); ?></div>
            </div>
            <div class="arrival stop d-flex flex-column justify-content-start align-items-end">
                <strong class="flight-number"><i class="fas fa-plane-arrival mr-2"></i><?= get_field('arrival_arrival_airport', $id)  ?></strong>
                <div class="date"><?= (new \DateTime(get_field('arrival_arrival_date_time', $id)))->format('F, j Y H:m'); ?></div>
            </div>
        </div>
    </div>
    
    <div class="flight mt-5">
        <div class="mb-3 text-center"><strong><?= __('Return Flight', 'hola-weddings') ?><br><?= get_field('return_flight_number', $id)  ?></strong></div>
        <div class="details-separator position-relative"></div>
        <div class="arrival-flight position-relative d-flex">
            <div class="departure stop">
                <strong class="airport"><i class="fas fa-plane-departure mr-2"></i><?= get_field('return_departure_airport', $id) ?></strong>
                <div class="date"><?= (new \DateTime(get_field('return_departure_date_time', $id)))->format('F, j Y H:m'); ?></div>
            </div>
            <div class="arrival stop d-flex flex-column justify-content-start align-items-end">
                <strong class="flight-number"><i class="fas fa-plane-arrival mr-2"></i><?= get_field('return_arrival_airport', $id)  ?></strong>
                <div class="date"><?= (new \DateTime(get_field('return_arrival_date_time', $id)))->format('F, j Y H:m'); ?></div>
            </div>
        </div>
    </div>
</div>
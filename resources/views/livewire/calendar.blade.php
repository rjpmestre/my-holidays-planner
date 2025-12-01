<div>
    <style>
        .btn-xs {
            padding: 2px 5px;
            font-size: 10px;
            line-height: 1.2;
        }
        td.position-relative {
            position: relative;
        }
        .type-buttons {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 3px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            padding: 2px;
        }
        .type-buttons .btn {
            margin: 1px 0;
            width: 100%;
        }
    </style>

    @if (session()->has('message'))
        <div class="alert alert-success">
            {{ session('message') }}
        </div>
    @endif

    <div class="row pt-3">
        <div class="col-6">
            <button class="btn btn-tools" wire:click="previousYear">&lt;</button>
            <strong>{{ $currentYear }}</strong>
            <button class="btn btn-tools" wire:click="nextYear">&gt;</button>
        </div>

        <div class="col-6 text-right">
            {{-- <h4>Dias: {{ $totalDaysSelected }}</h4> --}}
            <div class="text-lg">
                @if($editingLimit === 'regular')
                    <div class="d-inline-block">
                        <div class="input-group input-group-sm" style="width: 140px;">
                            <input type="number" class="form-control form-control-sm" wire:model="tempLimitValue" min="0" style="font-size: 0.875rem;">
                            <div class="input-group-append">
                                <button class="btn btn-success btn-sm" wire:click="saveLimit"><i class="fas fa-check"></i></button>
                                <button class="btn btn-secondary btn-sm" wire:click="cancelEdit"><i class="fas fa-times"></i></button>
                            </div>
                        </div>
                    </div>
                @else
                    <span class="badge badge-warning" style="cursor: pointer;" wire:click="editLimit('regular')" title="Clique para editar">Regular: {{ $regularDaysCount }}/{{ $maxVacationDays }}</span>
                @endif

                @if($editingLimit === 'carried')
                    <div class="d-inline-block">
                        <div class="input-group input-group-sm" style="width: 140px;">
                            <input type="number" class="form-control form-control-sm" wire:model="tempLimitValue" min="0" style="font-size: 0.875rem;">
                            <div class="input-group-append">
                                <button class="btn btn-success btn-sm" wire:click="saveLimit"><i class="fas fa-check"></i></button>
                                <button class="btn btn-secondary btn-sm" wire:click="cancelEdit"><i class="fas fa-times"></i></button>
                            </div>
                        </div>
                    </div>
                @else
                    <span class="badge badge-info" style="cursor: pointer;" wire:click="editLimit('carried')" title="Clique para editar">Transportados: {{ $carriedDaysCount }}@if($manualCarriedDays > 0)/{{ $manualCarriedDays }}@endif</span>
                @endif

                @if($editingLimit === 'volunteer')
                    <div class="d-inline-block">
                        <div class="input-group input-group-sm" style="width: 140px;">
                            <input type="number" class="form-control form-control-sm" wire:model="tempLimitValue" min="0" style="font-size: 0.875rem;">
                            <div class="input-group-append">
                                <button class="btn btn-success btn-sm" wire:click="saveLimit"><i class="fas fa-check"></i></button>
                                <button class="btn btn-secondary btn-sm" wire:click="cancelEdit"><i class="fas fa-times"></i></button>
                            </div>
                        </div>
                    </div>
                @else
                    <span class="badge badge-success" style="cursor: pointer;" wire:click="editLimit('volunteer')" title="Clique para editar">Voluntariado: {{ $volunteerDaysCount }}/{{ $maxVolunteerDays }}</span>
                @endif

                @if($bonusDaysCount > 0)
                    <span class="badge badge-primary">Bónus: {{ $bonusDaysCount }}</span>
                @endif
            </div>
        </div>
    </div>
    <div class="row pb-3">

        <div class="col-6">
            @foreach($warnings as $warning)
                <p class="text-warning mb-0">
                    <i class="fas fa-exclamation-circle" ></i>
                    {{ $warning }}
                </p>
            @endforeach
        </div>

        <div class="col-6 text-right d-flex justify-content-end align-items-center">
            <div class="d-inline-flex align-items-center mr-3">
                <span class="mr-2">Selecionar Pontes:</span>
                <button class="btn btn-primary ml-1" wire:click="selectBridges(1)">1</button>
                <button class="btn btn-primary ml-1" wire:click="selectBridges(2)">2</button>
                <button class="btn btn-primary ml-1" wire:click="selectBridges(3)">3</button>
                <button class="btn btn-primary ml-1" wire:click="selectBridges(4)">4</button>
            </div>

            <button type="button" class="btn btn-tool" wire:click="restoreVacationDays" title="Restaurar" >
                <i class="fas fa-sync"></i>
            </button>

            <button type="button" class="btn btn-tool" wire:click="clearVacationDays" title="Limpar">
                <i class="fas fa-eraser"></i>
            </button>

            <button type="button" class="btn btn-tool" wire:click="saveVacationDays" title="Guardar">
                <i class="fa fa-save"></i>
            </button>
        </div>

    </div>

    <div class="row">
        @for ($month = 1; $month <= 12; $month++)
            <div class="col-4" wire:key="month-{{ $month }}-{{ $currentYear }}">
                <div class="card calendar">
                    <div class="card-header text-uppercase text-center py-2">
                        {{ Carbon\Carbon::createFromDate($currentYear, $month, 1)->locale('pt_PT')->monthName }}
                        <span class="badge badge-light">{{ collect($selectedDays)->filter(function($day) use ($month) {
                            return Carbon\Carbon::parse($day)->month == $month;
                        })->count() }}</span>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th class="px-0 py-2">Do</th>
                                    <th class="px-0 py-2">Se</th>
                                    <th class="px-0 py-2">Te</th>
                                    <th class="px-0 py-2">Qu</th>
                                    <th class="px-0 py-2">Qu</th>
                                    <th class="px-0 py-2">Se</th>
                                    <th class="px-0 py-2">Sá</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $daysInMonth = Carbon\Carbon::createFromDate($currentYear, $month, 1)->daysInMonth;
                                    $firstDayOfMonth = Carbon\Carbon::createFromDate($currentYear, $month, 1)->dayOfWeek;
                                @endphp
                                <tr>
                                    @for ($i = 0; $i < $firstDayOfMonth; $i++)
                                        <td></td>
                                    @endfor

                                    @for ($day = 1; $day <= $daysInMonth; $day++)
                                        @php
                                            $date = Carbon\Carbon::createFromDate($currentYear, $month, $day);
                                            $dateFormatted = $date->format('Y-m-d');
                                            $holidays = $this->getHolidays($dateFormatted);
                                            $isInGroup = $this->isAlternativeHoliday($dateFormatted);
                                            $birthdays = $this->getBirthdays($dateFormatted);
                                            $tooltip = $holidays->pluck('description')->merge($birthdays->pluck('description'))->join('<br/>');
                                            $vacationType = $this->getVacationType($dateFormatted);
                                            $isSelected = in_array($dateFormatted, $selectedDays);
                                            $typeIcon = '';
                                            if ($vacationType) {
                                                if ($vacationType->id == 2) $typeIcon = '<i class="fas fa-forward text-info" title="Transportado"></i>';
                                                elseif ($vacationType->id == 3) $typeIcon = '<i class="fas fa-hands-helping text-success" title="Voluntariado"></i>';
                                                elseif ($vacationType->id == 4) $typeIcon = '<i class="fas fa-gift text-primary" title="Bónus"></i>';
                                            }
                                        @endphp
                                        <td
                                            class="px-0 py-2 position-relative {{ $this->getClasses($dateFormatted)}}"
                                            data-date="{{ $dateFormatted }}"
                                            oncontextmenu="event.preventDefault(); showTypeMenu('{{ $dateFormatted }}', event);"
                                            @if($tooltip)
                                                data-html="true"
                                                data-toggle="tooltip"
                                                data-placement="top"
                                                title="{!! $tooltip !!}"
                                            @endif
                                            @if($isInGroup)
                                                onmouseover="highlightGroup('{{ $dateFormatted }}')"
                                                onmouseout="clearHighlight()"
                                            @endif
                                        >
                                            <div wire:click="toggleDay('{{ $dateFormatted }}')" style="cursor: pointer;">
                                                {{ $day }}
                                                @if($typeIcon)
                                                    {!! $typeIcon !!}
                                                @endif
                                                @if($holidays->count() + $birthdays->count() > 1)
                                                    <span class="badge badge-light">{{ $holidays->count() + $birthdays->count() }}</span>
                                                @endif
                                            </div>

                                            @if($isSelected)
                                                <!-- Quick type change buttons for selected days -->
                                                <div class="type-buttons" style="display: none; position: absolute; top: 2px; right: 2px; z-index: 100;">
                                                    <div class="btn-group-vertical btn-group-sm">
                                                        <button type="button" class="btn btn-xs btn-warning"
                                                                wire:click.stop="changeVacationType('{{ $dateFormatted }}', 1)"
                                                                title="Regular">
                                                            <i class="fas fa-calendar-day"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-xs btn-info"
                                                                wire:click.stop="changeVacationType('{{ $dateFormatted }}', 2)"
                                                                title="Transportado">
                                                            <i class="fas fa-forward"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-xs btn-success"
                                                                wire:click.stop="changeVacationType('{{ $dateFormatted }}', 3)"
                                                                title="Voluntariado">
                                                            <i class="fas fa-hands-helping"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            @endif

                                        @if (($day + $firstDayOfMonth) % 7 == 0)
                                            </tr><tr>
                                        @endif
                                    @endfor

                                    @for ($i = ($firstDayOfMonth + $daysInMonth) % 7; $i < 7 && $i != 0; $i++)
                                        <td></td>
                                    @endfor
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endfor
    </div>

    <!-- Legend for vacation types -->
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Legenda</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Tipos de Dia:</h6>
                            <p class="mb-2"><i class="fas fa-square text-warning"></i> Férias Selecionadas</p>
                            <p class="mb-2"><i class="fas fa-square" style="color: #C1ECAC;"></i> Feriado Nacional</p>
                            <p class="mb-2"><i class="fas fa-square" style="color: #00bc8c66;"></i> Dia da Empresa</p>
                            <p class="mb-2"><i class="fas fa-square" style="color: #ffc6f561;"></i> Aniversário</p>
                        </div>
                        <div class="col-md-6">
                            <h6>Tipos de Férias:</h6>
                            <p class="mb-2"><i class="fas fa-calendar-day text-warning"></i> Regular (padrão)</p>
                            <p class="mb-2"><i class="fas fa-forward text-info"></i> Transportado</p>
                            <p class="mb-2"><i class="fas fa-hands-helping text-success"></i> Voluntariado</p>
                        </div>
                    </div>
                    <hr>
                    <p class="mb-0 text-muted">
                        <strong>Como usar:</strong><br>
                        • <strong>Clique esquerdo</strong> num dia para selecionar/desselecionar<br>
                        • <strong>Passe o rato</strong> sobre um dia selecionado para ver os botões de tipo<br>
                        • <strong>Clique direito</strong> num dia selecionado para menu de contexto
                    </p>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            // Function to highlight the group dates
            function highlightGroup(date) {
                const groupDates = @json($holidayGroups); // Get group dates from Livewire
                const datesToHighlight = [];

                // Find the group for the hovered date
                for (const group of groupDates) {
                    if (group.dates.includes(date)) {
                        datesToHighlight.push(...group.dates);
                        break;
                    }
                }

                // Highlight the relevant dates with a border
                datesToHighlight.forEach(function(date) {
                    const td = document.querySelector(`td[data-date="${date}"]`);
                    if (td) {
                        td.classList.add('highlight'); // Add a highlight class for borders
                    }
                });
            }

            // Function to clear highlights
            function clearHighlight() {
                const highlightedCells = document.querySelectorAll('td.highlight');
                highlightedCells.forEach(function(cell) {
                    cell.classList.remove('highlight'); // Remove the highlight class
                });
            }

            // Show/hide type change buttons on hover for selected days
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize Bootstrap tooltips
                $('[data-toggle="tooltip"]').tooltip({
                    html: true
                });

                const calendar = document.querySelector('.row');
                if (calendar) {
                    calendar.addEventListener('mouseover', function(e) {
                        const td = e.target.closest('td[data-date]');
                        if (td && td.querySelector('.type-buttons')) {
                            td.querySelector('.type-buttons').style.display = 'block';
                        }
                    });

                    calendar.addEventListener('mouseout', function(e) {
                        const td = e.target.closest('td[data-date]');
                        if (td && td.querySelector('.type-buttons')) {
                            td.querySelector('.type-buttons').style.display = 'none';
                        }
                    });
                }
            });

            // Reinitialize tooltips after Livewire updates
            document.addEventListener('livewire:updated', function() {
                $('[data-toggle="tooltip"]').tooltip('dispose');
                $('[data-toggle="tooltip"]').tooltip({
                    html: true
                });
            });

            // Context menu for type selection
            let contextMenu = null;

            function showTypeMenu(date, event) {
                // Only show menu for selected days
                const td = event.target.closest('td[data-date]');
                if (!td || !td.querySelector('.type-buttons')) {
                    return;
                }

                // Remove existing menu if any
                if (contextMenu) {
                    contextMenu.remove();
                }

                // Create menu
                contextMenu = document.createElement('div');
                contextMenu.className = 'context-menu';
                contextMenu.style.cssText = `
                    position: fixed;
                    left: ${event.clientX}px;
                    top: ${event.clientY}px;
                    background: white;
                    border: 1px solid #ccc;
                    border-radius: 4px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
                    z-index: 9999;
                    padding: 5px 0;
                    min-width: 150px;
                `;

                const menuItems = [
                    { icon: 'fas fa-calendar-day', text: 'Regular', color: '#ffc107', typeId: 1 },
                    { icon: 'fas fa-forward', text: 'Transportado', color: '#17a2b8', typeId: 2 },
                    { icon: 'fas fa-hands-helping', text: 'Voluntariado', color: '#28a745', typeId: 3 }
                ];

                menuItems.forEach(item => {
                    const menuItem = document.createElement('div');
                    menuItem.style.cssText = `
                        padding: 8px 15px;
                        cursor: pointer;
                        display: flex;
                        align-items: center;
                        gap: 10px;
                    `;
                    menuItem.innerHTML = `<i class="${item.icon}" style="color: ${item.color}; width: 20px;"></i> ${item.text}`;

                    menuItem.addEventListener('mouseover', function() {
                        this.style.backgroundColor = '#f0f0f0';
                    });
                    menuItem.addEventListener('mouseout', function() {
                        this.style.backgroundColor = 'white';
                    });
                    menuItem.addEventListener('click', function() {
                        // Trigger Livewire method
                        @this.call('changeVacationType', date, item.typeId);
                        contextMenu.remove();
                        contextMenu = null;
                    });

                    contextMenu.appendChild(menuItem);
                });

                document.body.appendChild(contextMenu);

                // Close menu when clicking elsewhere
                setTimeout(() => {
                    document.addEventListener('click', function closeMenu() {
                        if (contextMenu) {
                            contextMenu.remove();
                            contextMenu = null;
                        }
                        document.removeEventListener('click', closeMenu);
                    });
                }, 10);
            }
        </script>

    @endpush
</div>

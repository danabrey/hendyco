<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Booking;
use Guava\Calendar\Filament\CalendarWidget;
use Guava\Calendar\ValueObjects\DateClickInfo;
use Guava\Calendar\ValueObjects\EventClickInfo;
use Guava\Calendar\ValueObjects\FetchInfo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BookingsCalendarWidget extends CalendarWidget
{
    protected static bool $isDiscovered = false;

    protected bool $dateClickEnabled = true;

    protected bool $eventClickEnabled = true;

    /**
     * @var array<string, mixed>
     */
    protected array $options = [
        'headerToolbar' => [
            'start' => 'prev,next today',
            'center' => 'title',
            'end' => 'dayGridMonth,timeGridWeek',
        ],
        'firstDay' => 1,
    ];

    /**
     * @return Builder<Booking>
     */
    protected function getEvents(FetchInfo $info): Builder
    {
        return Booking::query()
            ->with(['service', 'dogs'])
            ->whereBetween('date', [$info->start->toDateString(), $info->end->toDateString()]);
    }

    protected function onDateClick(DateClickInfo $info): void
    {
        $this->redirect(BookingResource::getUrl('create', ['date' => $info->date->toDateString()]));
    }

    protected function onEventClick(EventClickInfo $info, Model $event, ?string $action = null): void
    {
        $this->redirect(BookingResource::getUrl('edit', ['record' => $event]));
    }
}

<?php

namespace Tests\Feature\Admin;

use App\Models\ExchangeCalendarEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExchangeCalendarCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admins_can_access_exchange_calendar_management(): void
    {
        $this->get('/admin/exchange-calendar')->assertRedirect('/login');

        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user)->get('/admin/exchange-calendar')->assertRedirect('/');

        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->get('/admin/exchange-calendar')
            ->assertOk()
            ->assertSee('NSE &amp; BSE Calendar', false);
    }

    public function test_admin_can_create_view_edit_and_delete_an_event(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $create = $this->actingAs($admin)->post(route('admin.exchange-calendar.store'), [
            'exchange' => 'NSE',
            'segment' => 'equity',
            'event_date' => '2026-10-02',
            'name' => 'Mahatma Gandhi Jayanti',
            'event_type' => 'holiday',
        ]);

        $event = ExchangeCalendarEvent::firstOrFail();
        $create->assertRedirect(route('admin.exchange-calendar.show', $event));
        $this->assertTrue($event->is_manually_overridden);
        $this->assertSame('manual', $event->source);

        $this->get(route('admin.exchange-calendar.show', $event))
            ->assertOk()
            ->assertSee('Mahatma Gandhi Jayanti');

        $this->put(route('admin.exchange-calendar.update', $event), [
            'exchange' => 'NSE',
            'segment' => 'equity',
            'event_date' => '2026-11-08',
            'name' => 'Diwali Muhurat Trading',
            'event_type' => 'muhurat',
            'session_start' => '18:15',
            'session_end' => '19:15',
        ])->assertRedirect(route('admin.exchange-calendar.show', $event));

        $this->assertDatabaseHas('exchange_calendar_events', [
            'id' => $event->id,
            'event_date' => '2026-11-08',
            'event_type' => 'muhurat',
            'session_start' => '18:15',
            'session_end' => '19:15',
            'is_manually_overridden' => true,
        ]);

        $this->delete(route('admin.exchange-calendar.destroy', $event))
            ->assertRedirect(route('admin.exchange-calendar.index'));
        $this->assertDatabaseMissing('exchange_calendar_events', ['id' => $event->id]);
    }

    public function test_admin_cannot_create_duplicate_exchange_segment_date(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        ExchangeCalendarEvent::create($this->eventData());

        $this->actingAs($admin)
            ->from(route('admin.exchange-calendar.create'))
            ->post(route('admin.exchange-calendar.store'), [
                'exchange' => 'NSE',
                'segment' => 'equity',
                'event_date' => '2026-01-26',
                'name' => 'Duplicate',
                'event_type' => 'holiday',
            ])
            ->assertRedirect(route('admin.exchange-calendar.create'))
            ->assertSessionHasErrors('event_date');

        $this->assertSame(1, ExchangeCalendarEvent::count());
    }

    private function eventData(): array
    {
        return [
            'exchange' => 'NSE',
            'segment' => 'equity',
            'event_date' => '2026-01-26',
            'name' => 'Republic Day',
            'event_type' => 'holiday',
            'source' => 'nse',
            'source_url' => 'https://example.test/calendar',
            'source_payload' => [],
            'synced_at' => now(),
        ];
    }
}

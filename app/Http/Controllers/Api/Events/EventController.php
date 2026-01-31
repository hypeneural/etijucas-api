<?php

namespace App\Http\Controllers\Api\Events;

use App\Domain\Events\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Events\EventCollection;
use App\Http\Resources\Events\EventResource;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class EventController extends Controller
{
    /**
     * List events with filters and pagination.
     *
     * GET /api/v1/events
     */
    public function index(Request $request): EventCollection
    {
        // Authenticated users: bypass cache for personalized data
        if ($request->user()) {
            return new EventCollection($this->fetchEvents($request));
        }

        // Anonymous users: cache for performance
        $cacheKey = 'events:list:' . md5($request->fullUrl());

        $events = Cache::remember($cacheKey, 60, function () use ($request) {
            return $this->fetchEvents($request);
        });

        return new EventCollection($events);
    }

    /**
     * Get upcoming events.
     *
     * GET /api/v1/events/upcoming
     */
    public function upcoming(Request $request): EventCollection
    {
        $query = $this->baseQuery($request)
            ->upcoming()
            ->orderByStartDate();

        $perPage = min($request->input('perPage', 15), 50);

        return new EventCollection($query->paginate($perPage));
    }

    /**
     * Get today's events.
     *
     * GET /api/v1/events/today
     */
    public function today(Request $request): EventCollection
    {
        $query = $this->baseQuery($request)
            ->today()
            ->orderByStartDate();

        $perPage = min($request->input('perPage', 15), 50);

        return new EventCollection($query->paginate($perPage));
    }

    /**
     * Get weekend events.
     *
     * GET /api/v1/events/weekend
     */
    public function weekend(Request $request): EventCollection
    {
        $query = $this->baseQuery($request)
            ->weekend()
            ->orderByStartDate();

        $perPage = min($request->input('perPage', 15), 50);

        return new EventCollection($query->paginate($perPage));
    }

    /**
     * Get featured events.
     *
     * GET /api/v1/events/featured
     */
    public function featured(Request $request): EventCollection
    {
        $query = $this->baseQuery($request)
            ->featured()
            ->upcoming()
            ->orderByPopularity();

        $perPage = min($request->input('perPage', 10), 50);

        return new EventCollection($query->paginate($perPage));
    }

    /**
     * Search events.
     *
     * GET /api/v1/events/search
     */
    public function search(Request $request): EventCollection
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
        ]);

        $query = $this->baseQuery($request)
            ->search($request->input('q'))
            ->orderByPopularity();

        $perPage = min($request->input('perPage', 15), 50);

        return new EventCollection($query->paginate($perPage));
    }

    /**
     * Get events by date.
     *
     * GET /api/v1/events/date/{date}
     */
    public function byDate(Request $request, string $date): EventCollection
    {
        $parsedDate = \Carbon\Carbon::parse($date);

        $query = $this->baseQuery($request)
            ->onDate($parsedDate)
            ->orderByStartDate();

        $perPage = min($request->input('perPage', 15), 50);

        return new EventCollection($query->paginate($perPage));
    }

    /**
     * Get events by month.
     *
     * GET /api/v1/events/month/{year}/{month}
     */
    public function byMonth(Request $request, int $year, int $month): EventCollection
    {
        $query = $this->baseQuery($request)
            ->inMonth($year, $month)
            ->orderByStartDate();

        $perPage = min($request->input('perPage', 50), 100);

        return new EventCollection($query->paginate($perPage));
    }

    /**
     * Get events by category.
     *
     * GET /api/v1/events/category/{slug}
     */
    public function byCategory(Request $request, string $slug): EventCollection
    {
        $query = $this->baseQuery($request)
            ->byCategory($slug)
            ->upcoming()
            ->orderByStartDate();

        $perPage = min($request->input('perPage', 15), 50);

        return new EventCollection($query->paginate($perPage));
    }

    /**
     * Get events by bairro.
     *
     * GET /api/v1/events/bairro/{bairro}
     */
    public function byBairro(Request $request, string $bairroId): EventCollection
    {
        $query = $this->baseQuery($request)
            ->byBairro($bairroId)
            ->upcoming()
            ->orderByStartDate();

        $perPage = min($request->input('perPage', 15), 50);

        return new EventCollection($query->paginate($perPage));
    }

    /**
     * Get events by venue.
     *
     * GET /api/v1/events/venue/{venue}
     */
    public function byVenue(Request $request, string $venueId): EventCollection
    {
        $query = $this->baseQuery($request)
            ->byVenue($venueId)
            ->upcoming()
            ->orderByStartDate();

        $perPage = min($request->input('perPage', 15), 50);

        return new EventCollection($query->paginate($perPage));
    }

    /**
     * Get events by tag.
     *
     * GET /api/v1/events/tag/{slug}
     */
    public function byTag(Request $request, string $slug): EventCollection
    {
        $query = $this->baseQuery($request)
            ->byTag($slug)
            ->upcoming()
            ->orderByStartDate();

        $perPage = min($request->input('perPage', 15), 50);

        return new EventCollection($query->paginate($perPage));
    }

    /**
     * Get events by organizer.
     *
     * GET /api/v1/events/organizer/{organizer}
     */
    public function byOrganizer(Request $request, string $organizerId): EventCollection
    {
        $query = $this->baseQuery($request)
            ->byOrganizer($organizerId)
            ->upcoming()
            ->orderByStartDate();

        $perPage = min($request->input('perPage', 15), 50);

        return new EventCollection($query->paginate($perPage));
    }

    /**
     * Get single event details.
     *
     * GET /api/v1/events/{event}
     */
    public function show(Request $request, Event $event): JsonResponse
    {
        // Only show published events to non-admin users
        if ($event->status !== EventStatus::Published) {
            $user = $request->user();
            if (!$user || !$user->hasAnyRole(['admin', 'moderator'])) {
                abort(404);
            }
        }

        $event->load([
            'category',
            'venue.bairro',
            'organizer',
            'ticket.lots',
            'schedules',
            'tags',
            'media',
            'links',
            'rsvps.user',
        ]);

        return response()->json([
            'data' => new EventResource($event),
            'success' => true,
        ]);
    }

    /**
     * Build base query with common filters and relationships.
     */
    private function baseQuery(Request $request)
    {
        $query = Event::query()
            ->with(['category', 'venue.bairro', 'ticket', 'tags'])
            ->published();

        // User interactions
        if ($request->user()) {
            $query->withUserInteractions($request->user()->id);
        }

        // Apply filters
        $this->applyFilters($query, $request);

        return $query;
    }

    /**
     * Fetch events with all filters applied.
     */
    private function fetchEvents(Request $request)
    {
        $query = $this->baseQuery($request);

        // Ordering
        $orderBy = $request->input('orderBy', 'startDateTime');
        $order = $request->input('order', 'asc');

        $query = match ($orderBy) {
            'popularityScore' => $query->orderByPopularity($order),
            'createdAt' => $query->orderBy('created_at', $order),
            default => $query->orderByStartDate($order),
        };

        // Pagination
        $perPage = min($request->input('perPage', 15), 50);

        return $query->paginate($perPage);
    }

    /**
     * Apply filters from request.
     */
    private function applyFilters($query, Request $request): void
    {
        // Search
        if ($request->filled('search')) {
            $query->search($request->input('search'));
        }

        // Category
        if ($request->filled('categoryId') || $request->filled('category')) {
            $category = $request->input('categoryId') ?? $request->input('category');
            $query->byCategory($category);
        }

        // Bairro
        if ($request->filled('bairroId')) {
            $query->byBairro($request->input('bairroId'));
        }

        // Venue
        if ($request->filled('venueId')) {
            $query->byVenue($request->input('venueId'));
        }

        // Organizer
        if ($request->filled('organizerId')) {
            $query->byOrganizer($request->input('organizerId'));
        }

        // Tags
        if ($request->filled('tags')) {
            $tags = explode(',', $request->input('tags'));
            $query->byTags($tags);
        }

        // Date range
        if ($request->filled('fromDate') || $request->filled('toDate')) {
            $fromDate = $request->input('fromDate') ? \Carbon\Carbon::parse($request->input('fromDate')) : null;
            $toDate = $request->input('toDate') ? \Carbon\Carbon::parse($request->input('toDate')) : null;
            $query->inDateRange($fromDate, $toDate);
        }

        // Date preset
        if ($request->filled('datePreset')) {
            $query = match ($request->input('datePreset')) {
                'today' => $query->today(),
                'tomorrow' => $query->tomorrow(),
                'weekend' => $query->weekend(),
                'this_week' => $query->thisWeek(),
                'this_month' => $query->thisMonth(),
                default => $query->upcoming(),
            };
        } else {
            // Default to upcoming events
            $query->upcoming();
        }

        // Price
        if ($request->filled('price')) {
            $query->byPrice($request->input('price'));
        }

        if ($request->filled('priceMin') || $request->filled('priceMax')) {
            $query->byPriceRange(
                $request->input('priceMin') ? (float) $request->input('priceMin') : null,
                $request->input('priceMax') ? (float) $request->input('priceMax') : null
            );
        }

        // Time of day
        if ($request->filled('timeOfDay')) {
            $query->byTimeOfDay($request->input('timeOfDay'));
        }

        // Age rating
        if ($request->filled('ageRating')) {
            $query->byAgeRating($request->input('ageRating'));
        }

        // Flags
        if ($request->boolean('accessibility')) {
            $query->accessible();
        }

        if ($request->boolean('parking')) {
            $query->withParking();
        }

        if ($request->boolean('outdoor')) {
            $query->outdoor();
        }

        if ($request->boolean('kids')) {
            $query->kidsFriendly();
        }

        if ($request->boolean('featured')) {
            $query->featured();
        }
    }
}

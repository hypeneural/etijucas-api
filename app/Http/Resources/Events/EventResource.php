<?php

namespace App\Http\Resources\Events;

use App\Domain\Events\Enums\LinkType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * Complete event details for single event view.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'category' => new EventCategoryResource($this->whenLoaded('category')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'descriptionShort' => $this->description_short,
            'descriptionFull' => $this->description_full,
            'startDateTime' => $this->start_datetime?->toIso8601String(),
            'endDateTime' => $this->end_datetime?->toIso8601String(),
            'venue' => new VenueResource($this->whenLoaded('venue')),
            'ticket' => $this->getTicketData(),
            'links' => $this->getLinksData(),
            'media' => $this->getMediaData(),
            'schedule' => EventScheduleResource::collection($this->whenLoaded('schedules')),
            'flags' => [
                'ageRating' => $this->age_rating?->value,
                'ageRatingLabel' => $this->age_rating?->label(),
                'outdoor' => $this->is_outdoor,
                'accessibility' => $this->has_accessibility,
                'parking' => $this->has_parking,
            ],
            'organizer' => new OrganizerResource($this->whenLoaded('organizer')),
            'rsvp' => $this->getRsvpData($user),
            'popularityScore' => $this->popularity_score,
            'isFeatured' => $this->is_featured,
            'status' => $this->status?->value,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),

            // User-specific fields
            'isFavorited' => $this->when(
                $user,
                fn() => $this->is_favorited ?? $this->isFavoritedBy($user?->id),
                null
            ),
            'userRsvpStatus' => $this->when(
                $user,
                fn() => $this->getUserRsvpStatus($user?->id)?->value,
                null
            ),
        ];
    }

    /**
     * Get ticket data formatted
     */
    protected function getTicketData(): ?array
    {
        if (!$this->relationLoaded('ticket') || !$this->ticket) {
            return null;
        }

        $ticket = $this->ticket;
        $lots = [];

        if ($ticket->relationLoaded('lots')) {
            $lots = $ticket->lots->map(fn($lot) => [
                'id' => $lot->id,
                'name' => $lot->name,
                'price' => (float) $lot->price,
                'available' => $lot->quantity_available,
                'isActive' => $lot->isCurrentlyAvailable(),
            ])->toArray();
        }

        return [
            'type' => $ticket->ticket_type?->value,
            'minPrice' => (float) $ticket->min_price,
            'maxPrice' => $ticket->max_price ? (float) $ticket->max_price : null,
            'currency' => $ticket->currency,
            'purchaseUrl' => $ticket->purchase_url,
            'purchaseInfo' => $ticket->purchase_info,
            'lots' => $lots,
        ];
    }

    /**
     * Get links organized by type
     */
    protected function getLinksData(): array
    {
        if (!$this->relationLoaded('links')) {
            return [];
        }

        $result = [
            'instagram' => null,
            'whatsapp' => null,
            'website' => null,
            'facebook' => null,
            'youtube' => null,
            'tiktok' => null,
            'other' => [],
        ];

        foreach ($this->links as $link) {
            $type = $link->link_type->value;

            if (array_key_exists($type, $result) && $result[$type] === null) {
                $result[$type] = $link->url;
            } else {
                $result['other'][] = [
                    'type' => $type,
                    'url' => $link->url,
                    'label' => $link->display_label,
                ];
            }
        }

        return $result;
    }

    /**
     * Get media data (cover + gallery)
     */
    protected function getMediaData(): array
    {
        $gallery = [];

        if ($this->relationLoaded('media')) {
            $gallery = $this->media->map(fn($media) => [
                'id' => $media->id,
                'type' => $media->media_type->value,
                'url' => $media->url,
                'thumbnail' => $media->thumbnail,
                'caption' => $media->caption,
            ])->toArray();
        }

        return [
            'coverImage' => $this->cover_image_url,
            'gallery' => $gallery,
        ];
    }

    /**
     * Get RSVP summary data
     */
    protected function getRsvpData($user): array
    {
        $goingCount = $this->going_count ?? 0;
        $maybeCount = $this->maybe_count ?? 0;

        $attendees = [];
        if ($this->relationLoaded('rsvps')) {
            $attendees = $this->rsvps
                ->where('status', \App\Domain\Events\Enums\RsvpStatus::Going)
                ->take(10)
                ->map(fn($rsvp) => [
                    'id' => $rsvp->user?->id,
                    'nome' => $rsvp->user?->nome,
                    'avatarUrl' => $rsvp->user?->avatar_url,
                ])
                ->filter(fn($a) => $a['id'] !== null)
                ->values()
                ->toArray();
        }

        return [
            'count' => $goingCount + $maybeCount,
            'goingCount' => $goingCount,
            'maybeCount' => $maybeCount,
            'attendees' => $attendees,
            'userStatus' => $user ? $this->getUserRsvpStatus($user->id)?->value : null,
        ];
    }
}

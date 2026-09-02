<?php

namespace App\Livewire\Announcements;

use App\Models\Announcement;
use App\Models\AnnouncementTarget;
use App\Models\Classroom;
use App\Models\ScoutUnit;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Manage extends Component
{
    public ?int $announcementId = null;

    public string $title = '';

    public string $body = '';

    public bool $is_public = false;

    public string $publish_at = '';

    public string $expires_at = '';

    public array $target_types = [];

    public array $classroom_ids = [];

    public array $scout_unit_ids = [];


    public function mount(
        ?int $announcementId = null
    ): void {
        if (! $announcementId) {
            return;
        }

        $announcement =
            Announcement::query()
                ->with('targets')
                ->findOrFail(
                    $announcementId
                );

        $this->announcementId =
            $announcement->id;

        $this->title =
            $announcement->title;

        $this->body =
            $announcement->body;

        $this->is_public =
            $announcement->is_public;

        $this->publish_at =
            $announcement->publish_at
                ?->format('Y-m-d\TH:i')
            ?? '';

        $this->expires_at =
            $announcement->expires_at
                ?->format('Y-m-d\TH:i')
            ?? '';

        $this->target_types =
            $announcement
                ->targets
                ->pluck(
                    'target_type'
                )
                ->unique()
                ->values()
                ->toArray();

        $this->classroom_ids =
            $announcement
                ->targets
                ->where(
                    'target_type',
                    'classroom'
                )
                ->pluck(
                    'target_id'
                )
                ->map(
                    fn ($id) =>
                        (string) $id
                )
                ->toArray();

        $this->scout_unit_ids =
            $announcement
                ->targets
                ->where(
                    'target_type',
                    'scout_unit'
                )
                ->pluck(
                    'target_id'
                )
                ->map(
                    fn ($id) =>
                        (string) $id
                )
                ->toArray();
    }


    protected function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'max:200',
            ],

            'body' => [
                'required',
                'string',
                'max:20000',
            ],

            'is_public' => [
                'boolean',
            ],

            'publish_at' => [
                'nullable',
                'date',
            ],

            'expires_at' => [
                'nullable',
                'date',
                'after:publish_at',
            ],

            'target_types' => [
                'required',
                'array',
                'min:1',
            ],

            'target_types.*' => [
                Rule::in([
                    'all_students',
                    'all_coaches',
                    'classroom',
                    'scout_unit',
                ]),
            ],

            'classroom_ids' => [
                'array',
            ],

            'classroom_ids.*' => [
                'integer',
                'exists:classrooms,id',
            ],

            'scout_unit_ids' => [
                'array',
            ],

            'scout_unit_ids.*' => [
                'integer',
                'exists:scout_units,id',
            ],
        ];
    }


    public function save(): void
    {
        abort_unless(
            auth()->user()->can(
                $this->announcementId
                    ? 'announcements.update'
                    : 'announcements.create'
            ),
            403
        );

        if ($this->announcementId) {
            $existing =
                Announcement::query()
                    ->findOrFail(
                        $this->announcementId
                    );

            if (
                $existing->status ===
                'published'
                &&
                ! auth()->user()->can(
                    'announcements.publish'
                )
            ) {
                abort(
                    403,
                    'Pengumuman yang sudah dipublikasikan tidak dapat diubah.'
                );
            }
        }

        $validated =
            $this->validate();

        if (
            in_array(
                'classroom',
                $validated[
                    'target_types'
                ],
                true
            )
            &&
            empty(
                $validated[
                    'classroom_ids'
                ]
            )
        ) {
            $this->addError(
                'classroom_ids',
                'Pilih minimal satu kelas.'
            );

            return;
        }

        if (
            in_array(
                'scout_unit',
                $validated[
                    'target_types'
                ],
                true
            )
            &&
            empty(
                $validated[
                    'scout_unit_ids'
                ]
            )
        ) {
            $this->addError(
                'scout_unit_ids',
                'Pilih minimal satu Regu/Barung.'
            );

            return;
        }

        DB::transaction(
            function () use (
                $validated
            ): void {

                $data = [
                    'title' =>
                        trim(
                            $validated['title']
                        ),

                    'body' =>
                        trim(
                            $validated['body']
                        ),

                    'is_public' =>
                        $validated[
                            'is_public'
                        ],

                    'publish_at' =>
                        $validated[
                            'publish_at'
                        ] ?: null,

                    'expires_at' =>
                        $validated[
                            'expires_at'
                        ] ?: null,

                    'updated_by' =>
                        auth()->id(),
                ];

                if (
                    $this->announcementId
                ) {
                    $announcement =
                        Announcement::query()
                            ->findOrFail(
                                $this->announcementId
                            );

                    $announcement->update(
                        $data
                    );
                } else {
                    $data['created_by'] =
                        auth()->id();

                    $data['status'] =
                        'draft';

                    $announcement =
                        Announcement::query()
                            ->create(
                                $data
                            );

                    $this->announcementId =
                        $announcement->id;
                }

                $announcement
                    ->targets()
                    ->delete();

                $this->saveTargets(
                    $announcement,
                    $validated
                );
            }
        );

        session()->flash(
            'success',
            'Pengumuman berhasil disimpan.'
        );
    }


    protected function saveTargets(
        Announcement $announcement,
        array $validated
    ): void {
        foreach (
            $validated['target_types']
            as $type
        ) {
            if (
                in_array(
                    $type,
                    [
                        'all_students',
                        'all_coaches',
                    ],
                    true
                )
            ) {
                AnnouncementTarget::query()
                    ->create([
                        'announcement_id' =>
                            $announcement->id,

                        'target_type' =>
                            $type,

                        'target_id' =>
                            null,
                    ]);
            }
        }


        if (
            in_array(
                'classroom',
                $validated[
                    'target_types'
                ],
                true
            )
        ) {
            foreach (
                $validated[
                    'classroom_ids'
                ]
                as $classroomId
            ) {
                AnnouncementTarget::query()
                    ->create([
                        'announcement_id' =>
                            $announcement->id,

                        'target_type' =>
                            'classroom',

                        'target_id' =>
                            $classroomId,
                    ]);
            }
        }


        if (
            in_array(
                'scout_unit',
                $validated[
                    'target_types'
                ],
                true
            )
        ) {
            foreach (
                $validated[
                    'scout_unit_ids'
                ]
                as $unitId
            ) {
                AnnouncementTarget::query()
                    ->create([
                        'announcement_id' =>
                            $announcement->id,

                        'target_type' =>
                            'scout_unit',

                        'target_id' =>
                            $unitId,
                    ]);
            }
        }
    }


    public function publish(
        NotificationService $notificationService
    ): void {
        abort_unless(
            auth()->user()->can(
                'announcements.publish'
            ),
            403
        );

        abort_unless(
            $this->announcementId,
            422,
            'Simpan pengumuman terlebih dahulu.'
        );

        $announcement =
            Announcement::query()
                ->with('targets')
                ->findOrFail(
                    $this->announcementId
                );

        abort_if(
            $announcement
                ->targets
                ->isEmpty(),
            422,
            'Pengumuman belum memiliki target.'
        );

        DB::transaction(
            function () use (
                $announcement
            ): void {
                $announcement->update([
                    'status' =>
                        'published',

                    'published_at' =>
                        now(),

                    'updated_by' =>
                        auth()->id(),
                ]);
            }
        );

        $notificationService
            ->publish(
                $announcement
            );

        session()->flash(
            'success',
            'Pengumuman berhasil dipublikasikan.'
        );
    }


    public function archive(): void
    {
        abort_unless(
            auth()->user()->can(
                'announcements.archive'
            ),
            403
        );

        $announcement =
            Announcement::query()
                ->findOrFail(
                    $this->announcementId
                );

        $announcement->update([
            'status' =>
                'archived',

            'updated_by' =>
                auth()->id(),
        ]);

        session()->flash(
            'success',
            'Pengumuman berhasil diarsipkan.'
        );
    }


    public function render()
    {
        $classrooms =
            Classroom::query()
                ->where(
                    'is_active',
                    true
                )
                ->orderBy('name')
                ->get();

        $scoutUnits =
            ScoutUnit::query()
                ->where(
                    'is_active',
                    true
                )
                ->orderBy('name')
                ->get();

        $announcement =
            $this->announcementId
                ? Announcement::query()
                    ->with('targets')
                    ->find(
                        $this->announcementId
                    )
                : null;

        return view(
            'livewire.announcements.manage',
            compact(
                'announcement',
                'classrooms',
                'scoutUnits'
            )
        );
    }
}
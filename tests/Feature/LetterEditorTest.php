<?php

use App\Livewire\Letters\Index;
use App\Livewire\Letters\Templates;
use App\Models\LetterTemplate;
use App\Models\School;
use App\Models\User;
use App\Support\SchoolContext;
use Livewire\Livewire;

test('template data updates the visual letter until manually edited and can be restored', function () {
    app(SchoolContext::class)->set(School::factory()->create());
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));
    $component = Livewire::test(Index::class, ['direction' => 'outgoing']);
    $template = LetterTemplate::query()->create([
        'slug' => 'editor-test', 'name' => 'Pengumuman editor', 'is_active' => true,
        'body_template' => '<p style="text-align: center"><strong>{{ subject }}</strong></p><p>{{ announcement_body }}</p>',
    ]);

    $component->set('template_id', $template->id)
        ->assertSet('useCustomBody', false)
        ->assertSet('showPreview', false)
        ->set('templateData.announcement_body', 'Latihan hari Jumat')
        ->assertSet('body', fn (string $body): bool => str_contains($body, 'Latihan hari Jumat'))
        ->set('subject', 'Pengumuman terbaru')
        ->assertSet('body', fn (string $body): bool => str_contains($body, 'Pengumuman terbaru'))
        ->set('body', '<p>Isi manual</p>')
        ->assertSet('useCustomBody', true)
        ->set('templateData.announcement_body', 'Latihan hari Sabtu')
        ->assertSet('body', '<p>Isi manual</p>')
        ->call('togglePreview')
        ->assertViewHas('previewBody', '<p>Isi manual</p>')
        ->call('useRenderedTemplate')
        ->assertSet('useCustomBody', false)
        ->assertSet('body', fn (string $body): bool => str_contains($body, 'Latihan hari Sabtu'));

    expect($template->fresh()->body_template)->toContain('{{ announcement_body }}');
});

test('template editor loads rich layout preserves placeholders and replaces starter content', function () {
    app(SchoolContext::class)->set(School::factory()->create());
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));
    $component = Livewire::test(Templates::class)->call('createNew');

    $component->call('applyStarter', 'assignment')
        ->assertDispatched('letter-editor-content', fn (string $event, array $params): bool => $params['property'] === 'body_template' && str_contains($params['html'], '{{'))
        ->set('name', 'Template CK')
        ->set('body_template', '<p style="text-align: center"><strong>PENGUMUMAN</strong></p><p>{{ announcement_body }}</p>')
        ->call('save')
        ->assertHasNoErrors();

    $template = LetterTemplate::query()->where('name', 'Template CK')->sole();
    expect($template->body_template)->toContain('text-align: center', '{{ announcement_body }}', '<strong>PENGUMUMAN</strong>');
    $component->call('edit', $template->id)->assertSet('body_template', $template->body_template);
});

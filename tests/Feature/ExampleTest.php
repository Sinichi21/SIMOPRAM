<?php

test('guests can visit the public home page', function () {
    $response = $this->get(route('home'));

    $response->assertOk()->assertSee('Pramuka tertata');
});

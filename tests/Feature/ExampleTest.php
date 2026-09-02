<?php

test('guests are redirected from the home page to login', function () {
    $response = $this->get(route('home'));

    $response->assertRedirect(route('login'));
});

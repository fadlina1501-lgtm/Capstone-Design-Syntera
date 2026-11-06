<?php

test('guest users are redirected to login from the application root', function () {
    $response = $this->get('/');

    $response->assertRedirect('/login');
});

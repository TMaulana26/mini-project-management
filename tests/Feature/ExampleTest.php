<?php

test('the application returns a redirect response', function () {
    $response = $this->get('/');

    $response->assertRedirect('/docs/api#/');
});

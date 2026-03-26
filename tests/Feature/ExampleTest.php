<?php

test('the application returns a successful response', function () {
    $response = $this->get(route('signin'));

    $response->assertRedirect(route('setup'));
});

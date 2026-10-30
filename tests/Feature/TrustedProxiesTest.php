<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::get('/__client-ip', fn (Request $request) => $request->ip());
});

function clientIpSeenBehind(string $proxyAddress, string $forwardedFor): string
{
    return test()
        ->withServerVariables(['REMOTE_ADDR' => $proxyAddress])
        ->withHeader('X-Forwarded-For', $forwardedFor)
        ->get('/__client-ip')
        ->getContent();
}

test('forwarded headers are ignored when no proxies are configured', function () {
    config(['trustedproxy.proxies' => null]);

    expect(clientIpSeenBehind('10.0.0.5', '93.184.216.34'))->toBe('10.0.0.5');
});

test('client IP comes from X-Forwarded-For when the proxy is trusted', function () {
    config(['trustedproxy.proxies' => '10.0.0.0/8']);

    expect(clientIpSeenBehind('10.0.0.5', '93.184.216.34'))->toBe('93.184.216.34');
});

test('spoofed X-Forwarded-For entries left of the real client are ignored', function () {
    config(['trustedproxy.proxies' => 'PRIVATE_SUBNETS']);

    expect(clientIpSeenBehind('127.0.0.1', '6.6.6.6, 93.184.216.34, 172.18.0.2'))->toBe('93.184.216.34');
});

test('requests from an untrusted address cannot spoof their IP', function () {
    config(['trustedproxy.proxies' => '10.0.0.0/8']);

    expect(clientIpSeenBehind('151.101.1.69', '93.184.216.34'))->toBe('151.101.1.69');
});

test('TRUSTED_PROXIES accepts a comma separated list', function () {
    config(['trustedproxy.proxies' => '127.0.0.1, 172.16.0.0/12']);

    expect(clientIpSeenBehind('127.0.0.1', '93.184.216.34, 172.18.0.2'))->toBe('93.184.216.34');
});

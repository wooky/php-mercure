<?php

declare(strict_types=1);

use Yakov\PhpMercure\PhpMercureConfig;

return new PhpMercureConfig(
    /*
     * publisherJwtKey: key that's used to generate and verify the JWT used by the publisher (i.e. server).
     * Key must be at least 256 bits.
     */
    '!ChangeThisMercureHubJWTSecretKey!',

    /*
     * subscriberJwtKey: key that's used to generate and verify the JWT used by the subscriber (i.e. client).
     * Key must be at least 256 bits.
     */
    '!ChangeThisMercureHubJWTSecretKey!'

    /*
     * mandatoryAuthorization: flag to force clients to be authorized for all topics they're subscribed to.
     */
    // , false

    /*
     * eventBusPort: port over which PHP processes will be communicating about received publications.
     */
    // , 12345

    /*
     * eventBusMaxPayload: maximum payload to send over the event bus.
     * Should not be higher than the maximum UDP packet size, approx. 2^16.
     */
    // , 65000
);

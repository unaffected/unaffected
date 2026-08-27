# Structure

Current tree. Update this when it moves.

```
source/
  Application/  Application.php  Context.php  Plugin.php  Transport.php
                Hook/  Hook.php  Hooks.php  Scope.php  Type.php
  Engine/       Engine.php
  Exception/    Failure.php
                AuthenticationException  AuthorizationException  GuardException
                NotFoundException  RemoteException  SchemaException
                ValidationException  VerificationException
  Gateway/      Gateway.php  Request.php  Response.php
  Network/      Network.php  Subject.php
  Schema/       Schema.php  Rule.php  Report.php  Finding.php  Value.php
                Callback.php
  Server/       Server.php  Envelope.php  Exchange.php  Route.php
                Routes/  Api.php  Rest.php  Socket.php
  Service/      Service.php  Action.php
                Health/  Health.php  Actions/  Check.php

tests/  mirrors source/ path for path
bin/    engine  gateway
```

Rules:

- A module is a directory. Its entry class is named for it.
- One level of nesting inside a module, maximum.
- Exceptions scope to their owner: platform-wide in `Exception/`, module-specific
  in `<Module>/Exception/`.
- Names are plain words spelled out in full. No abbreviations.

HTTP:

```
GET    /health/check     health.check
POST   /<service>/<action>   body is the input
GET    /<service>            find      (query string is the input)
POST   /<service>            create
GET    /<service>/<id>       get
PUT    /<service>/<id>       update
PATCH  /<service>/<id>       patch
DELETE /<service>/<id>       remove
```

An unmapped verb is 405. `/api` speaks the envelope; `/socket` is the WebSocket
edge; both go through `Server/Exchange.php`.

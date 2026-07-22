<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Enums;

enum RuleAction: string
{
    case Block = 'block';
    case Challenge = 'challenge';
    case CompressResponse = 'compress_response';
    case DdosDynamic = 'ddos_dynamic';
    case Execute = 'execute';
    case ForceConnectionClose = 'force_connection_close';
    case JsChallenge = 'js_challenge';
    case Log = 'log';
    case LogCustomField = 'log_custom_field';
    case ManagedChallenge = 'managed_challenge';
    case Redirect = 'redirect';
    case Rewrite = 'rewrite';
    case Route = 'route';
    case Score = 'score';
    case ServeError = 'serve_error';
    case SetCacheControl = 'set_cache_control';
    case SetCacheSettings = 'set_cache_settings';
    case SetCacheTags = 'set_cache_tags';
    case SetConfig = 'set_config';
    case Skip = 'skip';
    case TransformResponseHtml = 'transform_response_html';
}

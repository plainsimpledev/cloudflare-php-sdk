<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Enums;

enum RulesetPhase: string
{
    case DdosL4 = 'ddos_l4';
    case DdosL7 = 'ddos_l7';
    case HttpConfigSettings = 'http_config_settings';
    case HttpCustomErrors = 'http_custom_errors';
    case HttpLogCustomFields = 'http_log_custom_fields';
    case HttpRatelimit = 'http_ratelimit';
    case HttpRequestCacheSettings = 'http_request_cache_settings';
    case HttpRequestDynamicRedirect = 'http_request_dynamic_redirect';
    case HttpRequestFirewallCustom = 'http_request_firewall_custom';
    case HttpRequestFirewallManaged = 'http_request_firewall_managed';
    case HttpRequestLateTransform = 'http_request_late_transform';
    case HttpRequestOrigin = 'http_request_origin';
    case HttpRequestRedirect = 'http_request_redirect';
    case HttpRequestSanitize = 'http_request_sanitize';
    case HttpRequestSbfm = 'http_request_sbfm';
    case HttpRequestTransform = 'http_request_transform';
    case HttpResponseCacheSettings = 'http_response_cache_settings';
    case HttpResponseCompression = 'http_response_compression';
    case HttpResponseFirewallManaged = 'http_response_firewall_managed';
    case HttpResponseHeadersTransform = 'http_response_headers_transform';
    case MagicTransit = 'magic_transit';
    case MagicTransitIdsManaged = 'magic_transit_ids_managed';
    case MagicTransitManaged = 'magic_transit_managed';
    case MagicTransitRatelimit = 'magic_transit_ratelimit';
}

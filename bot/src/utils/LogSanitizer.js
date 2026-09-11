/**
 * LogSanitizer for Node.js bot services (Req 12)
 * Ensures phone numbers, emails, addresses, and message texts are sanitized in log files and console output.
 */
export class LogSanitizer {
    static maskPhone(phone) {
        if (!phone) return '[NO_PHONE]';
        const str = String(phone).trim();
        if (str.length <= 5) return '***';
        const prefix = str.slice(0, 3);
        const suffix = str.slice(-3);
        return prefix + '*'.repeat(Math.max(3, str.length - 6)) + suffix;
    }

    static maskEmail(email) {
        if (!email || !String(email).includes('@')) return '[NO_EMAIL]';
        const [local, domain] = String(email).split('@');
        if (local.length <= 2) return local[0] + '*@' + domain;
        return local[0] + '*'.repeat(Math.max(3, local.length - 2)) + local.slice(-1) + '@' + domain;
    }

    static redactMessage(message) {
        if (!message) return '[EMPTY_MESSAGE]';
        const trimmed = String(message).trim();
        if (/^(menu|list|help|start|hi|hello|track|status|track\s+[A-Za-z0-9-]+)$/i.test(trimmed)) {
            return `[COMMAND: ${trimmed}]`;
        }
        return `[REDACTED MESSAGE ${trimmed.length} chars]`;
    }

    static redactAddress(address) {
        if (!address) return '[NO_ADDRESS]';
        return '[REDACTED ADDRESS]';
    }

    static sanitizeMeta(meta = {}) {
        const sensitiveKeys = [
            'password', 'owner_password', 'current_password', 'new_password',
            'api_key', 'apikey', 'secret', 'token', 'bot_internal_token', 'verification_token'
        ];

        const sanitized = {};
        for (const [key, value] of Object.entries(meta)) {
            const lower = key.toLowerCase();
            if (sensitiveKeys.some(s => lower.includes(s))) {
                sanitized[key] = '[REDACTED SECRET]';
            } else if (lower.includes('phone') || lower.includes('mobile') || lower.includes('jid')) {
                sanitized[key] = typeof value === 'string' ? this.maskPhone(value) : value;
            } else if (lower.includes('email')) {
                sanitized[key] = typeof value === 'string' ? this.maskEmail(value) : value;
            } else if (lower.includes('address')) {
                sanitized[key] = typeof value === 'string' ? this.redactAddress(value) : value;
            } else if (lower.includes('message') || lower.includes('text') || lower.includes('caption')) {
                sanitized[key] = typeof value === 'string' ? this.redactMessage(value) : value;
            } else if (typeof value === 'object' && value !== null) {
                sanitized[key] = this.sanitizeMeta(value);
            } else {
                sanitized[key] = value;
            }
        }
        return sanitized;
    }
}

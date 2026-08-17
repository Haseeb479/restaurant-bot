import Groq from 'groq-sdk';

// Models tried in order — falls back if one is rate-limited or fails
const MODELS = [
    'llama-3.3-70b-versatile',
    'llama-3.1-8b-instant',
];

const REQUEST_DELAY_MS = parseInt(process.env.REQUEST_DELAY_MS) || 2000;
const lastRequestTime  = new Map(); // phone → timestamp

/**
 * GroqClient — Groq API wrapper with model fallback and per-phone rate limiting.
 * Tries each model in order; on rate-limit waits 3s and moves to the next.
 */
export class GroqClient {
    constructor() {
        const apiKey = process.env.GROQ_API_KEY;
        if (!apiKey) {
            console.log('⚠️  No GROQ_API_KEY found — fallback mode only');
            this.groq = null;
        } else {
            this.groq = new Groq({ apiKey });
            console.log('✅ Groq AI loaded');
        }
    }

    isAvailable() {
        return !!this.groq;
    }

    /**
     * Send messages to AI and get a reply.
     * @param {string} phoneKey  - Customer phone (used for per-phone throttle)
     * @param {Array}  messages  - Full message array [{role, content}, ...]
     * @returns {Promise<string|null>} AI reply, or null if all models fail
     */
    async chat(phoneKey, messages) {
        if (!this.groq) return null;

        // Per-phone request throttle
        const now  = Date.now();
        const last = lastRequestTime.get(phoneKey) || 0;
        const wait = REQUEST_DELAY_MS - (now - last);
        if (wait > 0) await new Promise(r => setTimeout(r, wait));
        lastRequestTime.set(phoneKey, Date.now());

        let lastError = null;

        for (const modelName of MODELS) {
            try {
                console.log(`🔄 Trying model: ${modelName}`);

                const completion = await this.groq.chat.completions.create({
                    model:       modelName,
                    messages,
                    max_tokens:  512,
                    temperature: 0.85,
                    top_p:       0.95,
                });

                const reply = completion.choices[0]?.message?.content?.trim();
                if (reply) {
                    console.log(`✅ Reply via ${modelName}`);
                    return reply;
                }

            } catch (err) {
                const isRateLimit = err.status === 429 ||
                    err.message?.includes('rate_limit_exceeded');

                if (isRateLimit) {
                    console.log(`⚠️  ${modelName} rate limited — trying next in 3s...`);
                    lastError = err;
                    await new Promise(r => setTimeout(r, 3000));
                    continue;
                }

                console.error(`❌ ${modelName} error: ${err.message}`);
                lastError = err;
                continue;
            }
        }

        console.error('❌ All models failed:', lastError?.message);
        return null;
    }
}

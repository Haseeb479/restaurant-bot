import fs from 'fs';
import path from 'path';
import Groq from 'groq-sdk';

// Vision-capable models on Groq (in priority order)
const VISION_MODELS = [
    'meta-llama/llama-4-scout-17b-16e-instruct',
    'meta-llama/llama-4-maverick-17b-128e-instruct',
];

/**
 * MenuOcrService — extracts menu items and prices from a menu image
 * using a Groq vision model.
 *
 * Results are cached in memory per restaurant so the image is only
 * analysed ONCE, not on every customer message.
 */
export class MenuOcrService {
    constructor() {
        const apiKey = process.env.GROQ_API_KEY;
        this.groq    = apiKey ? new Groq({ apiKey }) : null;
        // Cache: restaurantId → { text, extractedAt }
        this.cache   = new Map();
    }

    /**
     * Returns the menu text extracted from the image.
     * Uses cache if already extracted for this restaurant.
     *
     * @param {number} restaurantId
     * @param {string} imagePath  - Absolute path to the menu image
     * @returns {Promise<string|null>}  Extracted menu text or null on failure
     */
    async extractMenu(restaurantId, imagePath) {
        // Return cached result if available
        if (this.cache.has(restaurantId)) {
            return this.cache.get(restaurantId).text;
        }

        if (!this.groq) {
            console.warn('⚠️ MenuOcrService: No GROQ_API_KEY — cannot read menu image');
            return null;
        }

        if (!fs.existsSync(imagePath)) {
            console.warn(`⚠️ MenuOcrService: Image not found at ${imagePath}`);
            return null;
        }

        console.log(`🔍 MenuOCR: Reading menu image for restaurant #${restaurantId}...`);

        try {
            // Read image and convert to base64
            const imageBuffer  = fs.readFileSync(imagePath);
            const base64Image  = imageBuffer.toString('base64');
            const ext          = path.extname(imagePath).toLowerCase().replace('.', '');
            const mimeType     = ext === 'png' ? 'image/png'
                               : ext === 'webp' ? 'image/webp'
                               : 'image/jpeg';

            const imageUrl = `data:${mimeType};base64,${base64Image}`;

            let lastError = null;
            for (const model of VISION_MODELS) {
                try {
                    console.log(`🔄 MenuOCR: Trying vision model ${model}...`);

                    const completion = await this.groq.chat.completions.create({
                        model,
                        messages: [
                            {
                                role: 'user',
                                content: [
                                    {
                                        type: 'image_url',
                                        image_url: { url: imageUrl },
                                    },
                                    {
                                        type: 'text',
                                        text: `You are a menu reader. Extract ALL menu items and their prices from this restaurant menu image.

Output ONLY a clean, structured list in this exact format — nothing else:
ITEM NAME — Price (e.g. Rs.150)
If the item has sizes, list each size on its own line:
ITEM NAME (Small) — Rs.100
ITEM NAME (Large) — Rs.200

Rules:
- Include every single item visible
- Keep the exact names as shown in the menu
- Include ALL prices exactly as shown
- If price is unclear, write "price on request"
- Output ONLY the item list — no headings, no intro text, no extra commentary`,
                                    },
                                ],
                            },
                        ],
                        max_tokens: 1024,
                        temperature: 0.1, // Low temperature for accurate extraction
                    });

                    const text = completion.choices[0]?.message?.content?.trim();
                    if (text) {
                        console.log(`✅ MenuOCR: Extracted ${text.split('\n').length} lines for restaurant #${restaurantId}`);
                        this.cache.set(restaurantId, { text, extractedAt: Date.now() });
                        return text;
                    }

                } catch (err) {
                    const isRateLimit = err.status === 429 || err.message?.includes('rate_limit');
                    if (isRateLimit) {
                        console.log(`⚠️ MenuOCR: ${model} rate limited, trying next...`);
                        await new Promise(r => setTimeout(r, 3000));
                    } else {
                        console.error(`❌ MenuOCR: ${model} error: ${err.message}`);
                    }
                    lastError = err;
                }
            }

            console.error('❌ MenuOCR: All vision models failed:', lastError?.message);
            return null;

        } catch (err) {
            console.error('❌ MenuOcrService fatal error:', err.message);
            return null;
        }
    }

    /**
     * Clear the cache for a specific restaurant (e.g. after menu image update)
     */
    clearCache(restaurantId) {
        this.cache.delete(restaurantId);
        console.log(`🧹 MenuOCR cache cleared for restaurant #${restaurantId}`);
    }
}

// Singleton shared across all handlers
export const menuOcr = new MenuOcrService();

// test-groq.js
// Run this to check if your Groq key works
// Command: node test-groq.js

import Groq from 'groq-sdk';
import dotenv from 'dotenv';

dotenv.config({ path: '.env.llm' });

const apiKey = process.env.GROQ_API_KEY;

console.log('');
console.log('=== Groq API Test ===');
console.log('');

if (!apiKey || apiKey === 'paste_your_groq_key_here') {
    console.log('❌ ERROR: No API key found in .env.llm');
    console.log('');
    console.log('Steps to fix:');
    console.log('1. Go to https://console.groq.com/keys');
    console.log('2. Click "Create API Key"');
    console.log('3. Copy the key (starts with gsk_...)');
    console.log('4. Open .env.llm file');
    console.log('5. Add: GROQ_API_KEY=gsk_your_key_here');
    console.log('');
    process.exit(1);
}

console.log('✅ API key found:', apiKey.substring(0, 8) + '...');
console.log('');

const groq = new Groq({ apiKey });

const models = [
    'llama-3.3-70b-versatile',
    'llama-3.1-8b-instant',
    'mixtral-8x7b-32768',
];

async function testModel(modelName) {
    try {
        console.log(`Testing ${modelName}...`);
        const completion = await groq.chat.completions.create({
            model: modelName,
            messages: [{ role: 'user', content: 'Say hello in one sentence.' }],
            max_tokens: 50,
        });
        const text = completion.choices[0]?.message?.content?.trim();
        console.log(`✅ ${modelName} works! Reply: "${text?.substring(0, 60)}..."`);
        return true;
    } catch (err) {
        if (err.status === 429 || err.message?.includes('rate_limit')) {
            console.log(`⚠️  ${modelName} — Rate limited (try again in a moment)`);
        } else if (err.status === 401 || err.message?.includes('invalid_api_key')) {
            console.log(`❌ ${modelName} — Invalid API key`);
            console.log('   Get a new key at: https://console.groq.com/keys');
        } else if (err.status === 404 || err.message?.includes('not found')) {
            console.log(`❌ ${modelName} — Model not available`);
        } else {
            console.log(`❌ ${modelName} — Error: ${err.message}`);
        }
        return false;
    }
}

async function runTests() {
    let anyWorking = false;

    for (const model of models) {
        const ok = await testModel(model);
        if (ok) anyWorking = true;
        await new Promise(r => setTimeout(r, 1000));
    }

    console.log('');
    if (anyWorking) {
        console.log('🎉 At least one model works! Your bot should work.');
        console.log('   Run: node bot.js');
    } else {
        console.log('❌ No models working. Possible causes:');
        console.log('');
        console.log('1. API key is wrong or expired');
        console.log('   → Get new key: https://console.groq.com/keys');
        console.log('');
        console.log('2. Rate limit hit during testing');
        console.log('   → Wait 1 minute and run again');
        console.log('');
        console.log('3. Groq service temporarily down');
        console.log('   → Check status: https://status.groq.com');
    }
    console.log('');
}

runTests();
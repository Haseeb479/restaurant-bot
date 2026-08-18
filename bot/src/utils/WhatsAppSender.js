/**
 * WhatsAppSender — reliable message sender that resolves contact JIDs, handles @lid Linked Device accounts,
 * and falls back to active in-memory chat objects to ensure 100% message delivery.
 */
export async function sendWhatsAppText(client, phoneOrJid, message) {
    if (!client || !phoneOrJid || !message) {
        console.warn('⚠️ sendWhatsAppText: Missing client, phoneOrJid, or message');
        return false;
    }

    const clean = String(phoneOrJid).replace(/[^0-9]/g, '');
    if (!clean) return false;

    // Build list of target candidate JIDs (testing @lid, @c.us, and resolved IDs)
    const candidates = [];

    // If input already has @lid or @c.us
    if (String(phoneOrJid).includes('@')) {
        candidates.push(phoneOrJid);
    }

    // Try resolving registered number via WhatsApp API
    try {
        if (typeof client.getNumberId === 'function') {
            const numberDetails = await client.getNumberId(clean);
            if (numberDetails && numberDetails._serialized) {
                candidates.push(numberDetails._serialized);
            }
        }
    } catch (e) {
        console.debug('getNumberId notice:', e.message);
    }

    // Add both standard @c.us and WhatsApp privacy @lid formats
    if (!candidates.includes(`${clean}@lid`)) candidates.push(`${clean}@lid`);
    if (!candidates.includes(`${clean}@c.us`)) candidates.push(`${clean}@c.us`);

    // 1. Try sending to candidate JIDs
    for (const targetId of candidates) {
        try {
            await client.sendMessage(targetId, message);
            console.log(`📤 WhatsApp message successfully sent to: ${targetId}`);
            return true;
        } catch (sendErr) {
            console.warn(`⚠️ Direct send to ${targetId} failed: ${sendErr.message}`);

            // Try getChatById for this targetId
            try {
                const chat = await client.getChatById(targetId);
                if (chat) {
                    await chat.sendMessage(message);
                    console.log(`📤 WhatsApp message sent via getChatById(${targetId})`);
                    return true;
                }
            } catch (chatErr) {}
        }
    }

    // 2. Ultimate Fallback: search loaded WhatsApp chats for matching user
    try {
        console.log(`🔍 Searching active WhatsApp chats for user: ${clean}`);
        const chats = await client.getChats();
        for (const chat of chats) {
            if (chat.id?.user === clean || chat.id?._serialized?.includes(clean)) {
                await chat.sendMessage(message);
                console.log(`📤 WhatsApp message successfully sent via loaded chat (${chat.id._serialized})`);
                return true;
            }
        }
    } catch (searchErr) {
        console.error('❌ Active chats search error:', searchErr.message);
    }

    console.error(`❌ Could not deliver WhatsApp message to ${clean} after all fallbacks`);
    return false;
}

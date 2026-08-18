/**
 * WhatsAppSender — reliable message sender that resolves contact JIDs and avoids "No LID for user" errors.
 */
export async function sendWhatsAppText(client, phone, message) {
    if (!client || !phone || !message) {
        console.warn('⚠️ sendWhatsAppText: Missing client, phone, or message');
        return false;
    }

    const clean = phone.replace(/[^0-9]/g, '');
    let targetId = `${clean}@c.us`;

    // 1. Resolve registered WhatsApp ID (resolves internal LID / JID)
    try {
        if (typeof client.getNumberId === 'function') {
            const numberDetails = await client.getNumberId(clean);
            if (numberDetails && numberDetails._serialized) {
                targetId = numberDetails._serialized;
            }
        }
    } catch (e) {
        console.debug('getNumberId notice:', e.message);
    }

    // 2. Try sending directly
    try {
        await client.sendMessage(targetId, message);
        console.log(`📤 WhatsApp message sent to ${clean} (${targetId})`);
        return true;
    } catch (sendErr) {
        console.warn(`⚠️ Direct send to ${targetId} failed: ${sendErr.message}. Trying getChatById fallback...`);

        // 3. Fallback via getChatById
        try {
            const chat = await client.getChatById(targetId);
            if (chat) {
                await chat.sendMessage(message);
                console.log(`📤 WhatsApp message sent via chat object to ${clean}`);
                return true;
            }
        } catch (chatErr) {
            console.error(`❌ getChatById fallback failed for ${clean}:`, chatErr.message);
        }

        // 4. Try standard @c.us if targetId was modified
        if (targetId !== `${clean}@c.us`) {
            try {
                await client.sendMessage(`${clean}@c.us`, message);
                console.log(`📤 WhatsApp message sent to ${clean}@c.us fallback`);
                return true;
            } catch (fallbackErr) {
                console.error(`❌ Final fallback failed for ${clean}:`, fallbackErr.message);
            }
        }

        return false;
    }
}

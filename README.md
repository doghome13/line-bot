# line-bot
聊天機器人

* 使用 Messaging API 時，請確認在 Channel 的 Messaging API 頁籤中， 自動回應訊息 和 加入好友的歡迎訊息 的設定為 停用 狀態
* 用 chrome console 產生 Channel Access Token

- - -
(async () => {
  const pair = await crypto.subtle.generateKey(
    {
      name: 'RSASSA-PKCS1-v1_5',
      modulusLength: 2048,
      publicExponent: new Uint8Array([1, 0, 1]),
      hash: 'SHA-256'
    },
    true,
    ['sign', 'verify']
  );
   
  console.log('=== private key ===');
  console.log(JSON.stringify(await crypto.subtle.exportKey('jwk', pair.privateKey), null, '  '));
   
  console.log('=== public key ===');
  console.log(JSON.stringify(await crypto.subtle.exportKey('jwk', pair.publicKey), null, '  ')); 
})();
- - -

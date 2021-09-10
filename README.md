# line-bot
聊天機器人\
測試帳號 (LINE ID) @797ojgzl

# 功能
* 自動回覆 - 重複使用者輸入的話
* 靜音模式的切換 - (預設輸入) ON: `silent` / OFF: `speak`
* 開發 LOG - [/dev/logs](https://takolinebot.herokuapp.com/dev/logs)
* 例外訊息 LOG - [/dev/logs/exception](https://takolinebot.herokuapp.com/dev/logs/exception)
# 開發事項
* 使用 Messaging API 時，請確認在 Channel 的 Messaging API 頁籤中， 自動回應訊息 和 加入好友的歡迎訊息 的設定為 停用 狀態
* 用 chrome console 產生 Channel Access Token

  ```
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
  ```

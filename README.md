# line-bot
聊天機器人

測試帳號\
LINE ID `@797ojgzl`\
LINE LINK [https://liff.line.me/1645...](https://liff.line.me/1645278921-kWRPP32q?accountId=797ojgzl&openerPlatform=native&openerKey=unifiedSearch#mst_challenge=N7skpf0WNa4xY7WMBZH_J66XPHIzvZNyAvh5UuZmDOU)

# 功能
* 群組功能：
    1. 自動回覆訊息 - 複製你的字句，機率回覆特殊字句
    2. 靜音模式的切換 - (預設輸入) ON: `silent` / OFF: `speak`
    3. 輸入 `list!`，顯示操作選單
    4. 申請`管理員`、`小幫手`身分
* 個人功能：
    1. 輸入 `list!`，顯示操作選單
    2. 身分是`管理員`，則可以審核`小幫手`的申請
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

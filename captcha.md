# ReCAPTCHA Integration for React Frontend

## Overview

This document outlines how to use both ReCAPTCHA v2 and v3 integrations in our React frontend application. These integrations use Google's reCAPTCHA service to protect forms from spam and abuse.

## ReCAPTCHA v2

### Installation and Setup

1. Ensure the reCAPTCHA v2 script is included in your \`public/index.html\`:

   \`\`\`html
   <script src="https://www.google.com/recaptcha/api.js" async defer></script>
   \`\`\`

2. Copy the \`ReCaptcha\` component into your project (location: \`src/components/ReCaptcha.jsx\`).

### Usage

To use the ReCaptcha v2 component in your React application:

1. Import the component:

   \`\`\`jsx
   import ReCaptcha from './components/ReCaptcha';
   \`\`\`

2. Use the component in your JSX:

   \`\`\`jsx
   <ReCaptcha />
   \`\`\`

3. The component will render a reCAPTCHA widget and a "Verify" button. When the user completes the CAPTCHA and clicks "Verify", it will send the response to the backend for verification.

### API Reference (v2)

- **URL**: \`/api/verify-captcha-v2\`
- **Method**: \`POST\`
- **Body**:
  \`\`\`json
  {
    "g-recaptcha-response": "string"
  }
  \`\`\`
- **Success Response**:
  - **Code**: 200
  - **Content**: \`{ "success": true }\`

## ReCAPTCHA v3

### Installation and Setup

1. Copy the \`ReCaptchaV3\` component into your project (location: \`src/components/ReCaptchaV3.jsx\`).

2. Add your reCAPTCHA v3 site key to your environment variables:

   \`\`\`
   REACT_APP_RECAPTCHA_V3_SITE_KEY=your_site_key_here
   \`\`\`

### Usage

To use the ReCaptcha v3 component in your React application:

1. Import the component:

   \`\`\`jsx
   import ReCaptchaV3 from './components/ReCaptchaV3';
   \`\`\`

2. Use the component in your JSX:

   \`\`\`jsx
   <ReCaptchaV3 
     action="homepage" 
     onVerified={(score) => console.log('Verified with score:', score)} 
   />
   \`\`\`

3. The component will automatically load the reCAPTCHA v3 script and execute it. It doesn't render any visible elements.

### API Reference (v3)

- **URL**: \`/api/verify-captcha-v3\`
- **Method**: \`POST\`
- **Body**:
  \`\`\`json
  {
    "token": "string",
    "action": "string"
  }
  \`\`\`
- **Success Response**:
  - **Code**: 200
  - **Content**: \`{ "success": true, "score": number }\`

## Troubleshooting

1. For v2, if the reCAPTCHA widget doesn't appear, ensure the script is properly loaded in \`index.html\`.
2. For v3, check the browser console for any JavaScript errors related to script loading.
3. Verify that the site keys used in the React components match the ones in your reCAPTCHA admin console.
4. If verification fails, check the network tab in your browser's developer tools to see the response from the backend.

For any further issues, please contact the backend team or refer to the [official reCAPTCHA documentation](https://developers.google.com/recaptcha/docs/display).


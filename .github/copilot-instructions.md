# LostDocUg workspace instructions

- Use Laravel conventions, Blade views, and the existing Vite/Tailwind setup.
- Treat this repository as an early product prototype until a feature is backed by server-side workflows and tests.
- Never add public searches or galleries for identity documents or personal identifiers.
- Never collect or store bank-card numbers, PINs, CVVs, magnetic-stripe data, or other payment credentials.
- Keep report images in private storage and require server-side authorization for every sensitive action.
- Do not imply OCR matches prove ownership or authenticity; high-risk claims require human review.
- Add feature tests for authorization, privacy boundaries, and state transitions when implementing backend workflows.
- Do not assume government, bank, school, or other institutional integrations are authorized or available.
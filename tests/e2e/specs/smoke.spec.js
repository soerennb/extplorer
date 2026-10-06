const fs = require("node:fs/promises");
const path = require("node:path");
const { test, expect } = require("@playwright/test");

const username = process.env.E2E_USERNAME || "e2e-admin";
const initialPassword = process.env.E2E_PASSWORD || "e2e-admin-password";
let password = initialPassword;
const updatedPassword = "E2e-New-Password!2026";
const fixturePath = path.resolve(__dirname, "../fixtures/smoke-upload.txt");

async function monitorPage(page) {
  const pageErrors = [];
  const failedResponses = [];

  page.on("pageerror", (error) => pageErrors.push(error.message));
  page.on("response", (response) => {
    // A legacy persistent installation may still need to complete its
    // one-time password gate while the smoke suite is running.
    const expectedPasswordGate =
      response.status() === 403 && response.url().includes("/api/ls");
    const expectedPasswordRejection =
      response.status() === 400 && response.url().includes("/api/profile/password");
    const expectedSettingsStepUp =
      response.status() === 428 && response.url().endsWith("/api/settings");
    if (
      response.status() >= 400 &&
      !expectedPasswordGate &&
      !expectedPasswordRejection &&
      !expectedSettingsStepUp
    ) {
      failedResponses.push(`${response.status()} ${response.url()}`);
    }
  });
  await page.addInitScript(() => {
    window.__e2eCspViolations = [];
    document.addEventListener("securitypolicyviolation", (event) => {
      window.__e2eCspViolations.push({
        blockedURI: event.blockedURI,
        directive: event.effectiveDirective,
      });
    });
  });

  return async () => {
    const cspViolations = await page.evaluate(
      () => window.__e2eCspViolations || [],
    );
    expect(pageErrors, "uncaught browser errors").toEqual([]);
    expect(failedResponses, "failed browser requests").toEqual([]);
    expect(cspViolations, "Content Security Policy violations").toEqual([]);
  };
}

async function login(page) {
  await page.goto("/login");
  await page.locator("#login_username").fill(username);
  await page.locator("#login_password").fill(password);
  await page.getByTestId("login-submit").click();
  await expect(page.getByTestId("app-shell")).toBeVisible();
  await page.waitForLoadState("networkidle");
  await expect
    .poll(() => page.evaluate(() => window.forcePasswordChange === true))
    .toBe(false);
  await completeRequiredPasswordChange(page);
  await expect(page.locator(".swal2-container")).toHaveCount(0);
}

async function completeRequiredPasswordChange(page) {
  const newPasswordInput = page.locator("#profile-new-password");
  const passwordChangeRequired = await page.evaluate(
    () => window.forcePasswordChange === true,
  );
  if (!passwordChangeRequired) return;
  await expect(newPasswordInput).toBeVisible();

  await page.locator("#profile-current-password").fill(password);
  await newPasswordInput.fill(updatedPassword);
  await page.locator("#profile-confirm-password").fill(updatedPassword);
  await page.locator("#profile-panel-security button").filter({ hasText: "Update" }).click();
  await expect(page.locator("#profile-new-password")).toHaveValue("");
  password = updatedPassword;
  await page.locator("#userProfileModal .btn-close").click();
  await expect(page.locator("#userProfileModal")).toBeHidden();
}

async function restoreInitialPassword(page) {
  await page.getByTestId("user-menu").click();
  await page.locator(".dropdown-menu.show a.dropdown-item").first().click();
  await page.locator("#profile-tab-security").click();
  await expect(page.locator("#profile-new-password")).toBeVisible();
  await page.locator("#profile-current-password").fill(password);
  await page.locator("#profile-new-password").fill(initialPassword);
  await page.locator("#profile-confirm-password").fill(initialPassword);
  await page.locator("#profile-panel-security button").filter({ hasText: "Update" }).click();
  await expect(page.locator("#profile-new-password")).toHaveValue("");
  password = initialPassword;
  await page.locator("#userProfileModal .btn-close").click();
  await expect(page.locator("#userProfileModal")).toBeHidden();
}

function fileItem(page, name) {
  return page.locator(`[data-testid="file-item"][data-file-name="${name}"]`);
}

async function confirmDialog(page) {
  await page.locator(".swal2-confirm").click();
}

async function uploadTextFile(page, name, content) {
  await page.getByTestId("upload").click();
  await page.locator("#uploadFileInput").setInputFiles({
    name,
    mimeType: "text/plain",
    buffer: Buffer.from(content),
  });
  await page.getByTestId("upload-submit").click();
  await expect(page.locator(".swal2-success")).toBeVisible();
  await confirmDialog(page);
  await page.getByTestId("upload-close").click();
  await expect(fileItem(page, name)).toBeVisible();
}

async function verifyVendorBundles(page) {
  const loadedBundles = await page.evaluate(() => ({
    vue: typeof window.Vue?.createApp === "function",
    bootstrap: typeof window.bootstrap?.Modal === "function",
    sweetalert: typeof window.Swal?.fire === "function",
    ace: typeof window.ace?.edit === "function",
    diff: typeof window.Diff?.createPatch === "function",
    diff2html: typeof window.Diff2HtmlUI === "function",
  }));
  expect(loadedBundles).toEqual({
    vue: true,
    bootstrap: true,
    sweetalert: true,
    ace: true,
    diff: true,
    diff2html: true,
  });
}

test("running stack exposes health and protects application routes", async ({
  page,
  request,
}) => {
  const assertCleanBrowser = await monitorPage(page);

  const health = await request.get("/health");
  expect(health.status()).toBe(200);
  expect(await health.json()).toMatchObject({ status: "ok" });

  const api = await request.get("/api/ls", {
    headers: { Accept: "application/json" },
  });
  expect(api.status()).toBe(401);
  expect(await api.json()).toMatchObject({
    status: "error",
    code: "auth_required",
  });

  await page.goto("/");
  await expect(page).toHaveURL(/\/login\?return=%2F$/);
  await expect(page.locator('input[name="return"]')).toHaveValue("/");
  await assertCleanBrowser();
});

test("installed stack does not expose the installer or claim token", async ({
  request,
}) => {
  const install = await request.get("/install", { maxRedirects: 0 });
  expect(install.status()).toBe(302);
  expect(
    new URL(
      install.headers().location,
      process.env.E2E_BASE_URL || "http://127.0.0.1:8080",
    ).pathname,
  ).toBe("/");

  for (const path of [
    "/.extplorer-install-token",
    "/writable/.extplorer-install-token",
  ]) {
    const token = await request.get(path);
    expect(token.status(), `unexpectedly exposed ${path}`).not.toBe(200);
  }
});

test("local user can reject invalid credentials, sign in, and sign out", async ({
  page,
}) => {
  const assertCleanBrowser = await monitorPage(page);

  await page.goto("/login");
  await page.locator("#login_username").fill(username);
  await page.locator("#login_password").fill("incorrect-password");
  await page.getByTestId("login-submit").click();
  await expect(page.locator(".alert-danger")).toBeVisible();
  await expect(page).toHaveURL(/\/login/);

  await page.locator("#login_username").fill(username);
  await page.locator("#login_password").fill(password);
  await page.getByTestId("login-submit").click();
  await expect(page.getByTestId("app-shell")).toBeVisible();
  await completeRequiredPasswordChange(page);

  await page.getByTestId("user-menu").click();
  await page.locator(".dropdown-menu.show a.dropdown-item").first().click();
  await page.locator("#profile-tab-security").click();
  await page.locator("#profile-current-password").fill("incorrect-password");
  await page.locator("#profile-new-password").fill(updatedPassword);
  await page.locator("#profile-confirm-password").fill(updatedPassword);
  await page.locator("#profile-panel-security button").filter({ hasText: "Update" }).click();
  await expect(page.locator("#profile-panel-security .alert-danger")).toContainText(
    "Current password is incorrect.",
  );
  await page.locator("#userProfileModal .btn-close").click();
  await expect(page.locator("#userProfileModal")).toBeHidden();

  await restoreInitialPassword(page);
  await page.getByTestId("user-menu").click();
  await page.getByTestId("logout").click();
  await expect(page).toHaveURL(/\/login$/);
  await page.goto("/");
  await expect(page).toHaveURL(/\/login\?return=%2F$/);
  await assertCleanBrowser();
});

test("admin can save settings after current-password confirmation", async ({
  page,
}) => {
  const assertCleanBrowser = await monitorPage(page);

  await login(page);
  await page.goto("/admin#settings/mounts");

  const endpointAllowlist = page.locator(
    'textarea[aria-label*="Remote endpoint"]',
  );
  await expect(endpointAllowlist).toBeVisible();

  // Whitespace-only input must be accepted as an empty allowlist and does not
  // change the persisted policy, making the test safe for a shared smoke stack.
  await endpointAllowlist.fill("\n  \r\n");
  const challengeResponse = page.waitForResponse(
    (response) =>
      response.url().endsWith("/api/settings") &&
      response.request().method() === "POST" &&
      response.status() === 428,
  );
  const saveResponse = page.waitForResponse(
    (response) =>
      response.url().endsWith("/api/settings") &&
      response.request().method() === "POST" &&
      response.status() === 200,
  );
  await page.getByRole("button", { name: "Save Settings" }).click();
  expect((await challengeResponse).headers()["x-csrf-hash"]).toBeTruthy();

  const stepUpInput = page.locator("#step-up-password");
  await expect(stepUpInput).toBeVisible();
  await stepUpInput.fill(password);
  const stepUpResponse = page.waitForResponse(
    (response) =>
      response.url().endsWith("/api/security/step-up") &&
      response.request().method() === "POST",
  );
  await page.locator(".swal2-confirm").click();
  const confirmed = await stepUpResponse;
  expect(confirmed.status()).toBe(200);
  expect(confirmed.headers()["x-csrf-hash"]).toBeTruthy();
  expect((await saveResponse).headers()["x-csrf-hash"]).toBeTruthy();
  await expect(page.locator(".swal2-toast")).toContainText("Settings saved.");

  await endpointAllowlist.fill(" \n");
  const repeatedSaveResponse = page.waitForResponse(
    (response) =>
      response.url().endsWith("/api/settings") &&
      response.request().method() === "POST",
  );
  await page.getByRole("button", { name: "Save Settings" }).click();
  const repeatedSave = await repeatedSaveResponse;
  expect(repeatedSave.status()).toBe(200);
  expect(repeatedSave.headers()["x-csrf-hash"]).toBeTruthy();
  await expect(stepUpInput).toHaveCount(0);
  await expect(page.getByRole("button", { name: "Save Settings" })).toBeDisabled();

  await assertCleanBrowser();
});

test("local user can edit, compare, download, and restore files", async ({ page }) => {
  const assertCleanBrowser = await monitorPage(page);
  const folderName = "e2e-smoke-folder";
  const originalName = "smoke-upload.txt";
  const renamedName = "smoke-renamed.txt";

  await login(page);
  await verifyVendorBundles(page);

  await fileItem(page, "Home").dblclick();
  await expect(page.getByTestId("current-path")).toContainText("Home");

  await page.getByTestId("create-folder").click();
  await page.locator(".swal2-input").fill(folderName);
  await confirmDialog(page);
  await expect(fileItem(page, folderName)).toBeVisible();
  await fileItem(page, folderName).dblclick();
  await expect(page.getByTestId("current-path")).toContainText(folderName);

  await page.getByTestId("upload").click();
  await page.locator("#uploadFileInput").setInputFiles(fixturePath);
  await page.getByTestId("upload-submit").click();
  await expect(page.locator(".swal2-success")).toBeVisible();
  await confirmDialog(page);
  await page.getByTestId("upload-close").click();
  await expect(fileItem(page, originalName)).toBeVisible();

  await fileItem(page, originalName).dblclick();
  await expect(page.locator("#editorModal")).toBeVisible();
  const updatedContent = "updated vendor editor line\nshared line\n";
  await expect(page.locator("#aceEditor")).toContainText("end-to-end smoke fixture.");
  await page.evaluate(
    (content) => window.ace.edit("aceEditor").setValue(content, -1),
    updatedContent,
  );
  expect(await page.evaluate(() => window.ace.edit("aceEditor").getValue())).toBe(
    updatedContent,
  );
  const saveResponsePromise = page.waitForResponse(
    (response) =>
      response.url().endsWith("/api/save") &&
      response.request().method() === "POST",
  );
  await page.locator("#editorModal .modal-footer .btn-primary").click();
  expect((await saveResponsePromise).status()).toBe(200);
  await expect(page.locator("#editorModal")).toBeHidden();
  await expect(page.locator(".swal2-success")).toBeVisible();
  await confirmDialog(page);

  await fileItem(page, originalName).click();
  await page.getByTestId("selection-more").click();
  await page.getByRole("link", { name: "Version History" }).click();
  await expect(page.locator("#fileHistoryModal")).toBeVisible();
  await expect(page.locator("#fileHistoryModal tbody tr")).toHaveCount(1);
  const historyIcon = await page
    .locator("#fileHistoryModal .ri-history-line")
    .evaluate((element) => getComputedStyle(element, "::before").content);
  expect(historyIcon).not.toBe("none");
  await page.locator("#fileHistoryModal .btn-close").click();
  await expect(page.locator("#fileHistoryModal")).toBeHidden();

  const comparisonName = `vendor-diff-${Date.now().toString(36)}.txt`;
  await uploadTextFile(
    page,
    comparisonName,
    "comparison vendor diff line\nshared line\n",
  );
  await fileItem(page, originalName).click();
  await fileItem(page, comparisonName).click({ modifiers: ["Control"] });
  await page.getByTestId("selection-more").click();
  await page.getByRole("link", { name: "Diff" }).click();
  await expect(page.locator("#diffModal")).toBeVisible();
  await expect(page.locator("#diffViewer .d2h-file-wrapper")).toBeVisible();
  await expect(page.locator("#diffViewer")).toContainText(
    "updated vendor editor line",
  );
  await expect(page.locator("#diffViewer")).toContainText(
    "comparison vendor diff line",
  );
  await page.locator("#diffModal .btn-close").click();

  await fileItem(page, comparisonName).click();
  await page.getByTestId("selection-delete").click();
  await confirmDialog(page);
  await expect(fileItem(page, comparisonName)).toHaveCount(0);

  await fileItem(page, originalName).click();
  const downloadPromise = page.waitForEvent("download");
  await page.getByTestId("selection-download").click();
  const download = await downloadPromise;
  expect(download.suggestedFilename()).toBe(originalName);
  const downloadedPath = await download.path();
  expect(downloadedPath).not.toBeNull();
  expect(await fs.readFile(downloadedPath, "utf8")).toBe(updatedContent);

  await page.getByTestId("selection-more").click();
  await page.getByTestId("selection-rename").click();
  await page.locator(".swal2-input").fill(renamedName);
  await confirmDialog(page);
  await expect(fileItem(page, renamedName)).toBeVisible();

  await fileItem(page, renamedName).click();
  await page.getByTestId("selection-delete").click();
  await confirmDialog(page);
  await expect(fileItem(page, renamedName)).toHaveCount(0);

  await page.getByTestId("trash-toggle").click();
  await expect(fileItem(page, renamedName)).toBeVisible();
  await fileItem(page, renamedName).click();
  await page.getByTestId("selection-restore").click();
  await expect(page.locator(".swal2-success")).toBeVisible();
  await confirmDialog(page);
  await expect(fileItem(page, renamedName)).toHaveCount(0);

  await page.getByTestId("trash-toggle").click();
  await expect(fileItem(page, renamedName)).toBeVisible();
  await fileItem(page, renamedName).click();
  await page.getByTestId("selection-delete").click();
  await confirmDialog(page);
  await page.getByTestId("go-up").click();
  await expect(fileItem(page, folderName)).toBeVisible();
  await fileItem(page, folderName).click();
  await page.getByTestId("selection-delete").click();
  await confirmDialog(page);
  await page.getByTestId("trash-toggle").click();
  await page.getByTestId("empty-trash").click();
  await confirmDialog(page);
  await expect(page.getByTestId("file-item")).toHaveCount(0);

  await assertCleanBrowser();
});

test("local user can copy a folder with nested contents", async ({ page }) => {
  const assertCleanBrowser = await monitorPage(page);
  const suffix = Date.now().toString(36);
  const sourceName = `e2e-copy-source-${suffix}`;
  const targetName = `e2e-copy-target-${suffix}`;
  const nestedFileName = "smoke-upload.txt";

  await login(page);

  await fileItem(page, "Home").dblclick();
  await expect(page.getByTestId("current-path")).toContainText("Home");

  await page.getByTestId("create-folder").click();
  await page.locator(".swal2-input").fill(sourceName);
  await confirmDialog(page);
  await expect(fileItem(page, sourceName)).toBeVisible();
  await fileItem(page, sourceName).dblclick();

  await page.getByTestId("upload").click();
  await page.locator("#uploadFileInput").setInputFiles(fixturePath);
  await page.getByTestId("upload-submit").click();
  await expect(page.locator(".swal2-success")).toBeVisible();
  await confirmDialog(page);
  await page.getByTestId("upload-close").click();
  await expect(fileItem(page, nestedFileName)).toBeVisible();

  await page.getByTestId("go-up").click();
  await expect(fileItem(page, sourceName)).toBeVisible();
  await fileItem(page, sourceName).click();
  await page.locator('button[title="Copy"]').click();

  await page.getByTestId("create-folder").click();
  await page.locator(".swal2-input").fill(targetName);
  await confirmDialog(page);
  await expect(fileItem(page, targetName)).toBeVisible();
  await fileItem(page, targetName).dblclick();

  const copyResponse = page.waitForResponse(
    (response) =>
      response.url().endsWith("/api/cp") &&
      response.request().method() === "POST" &&
      response.status() === 200,
  );
  await page.locator('button[title="Paste"]').click();
  await copyResponse;
  await expect(page.locator(".swal2-success")).toBeVisible();
  await confirmDialog(page);
  await expect(fileItem(page, sourceName)).toBeVisible();

  await fileItem(page, sourceName).dblclick();
  await expect(fileItem(page, nestedFileName)).toBeVisible();

  await page.getByTestId("go-up").click();
  await page.getByTestId("go-up").click();
  await expect(fileItem(page, sourceName)).toBeVisible();
  await expect(fileItem(page, targetName)).toBeVisible();

  await fileItem(page, targetName).click();
  await page.getByTestId("selection-delete").click();
  await confirmDialog(page);
  await expect(fileItem(page, targetName)).toHaveCount(0);

  await fileItem(page, sourceName).click();
  await page.getByTestId("selection-delete").click();
  await confirmDialog(page);
  await expect(fileItem(page, sourceName)).toHaveCount(0);

  await page.getByTestId("trash-toggle").click();
  await expect(fileItem(page, targetName)).toBeVisible();
  await expect(fileItem(page, sourceName)).toBeVisible();
  await page.getByTestId("empty-trash").click();
  await confirmDialog(page);
  await expect(page.getByTestId("file-item")).toHaveCount(0);

  await assertCleanBrowser();
});

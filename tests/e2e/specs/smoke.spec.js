const fs = require("node:fs/promises");
const path = require("node:path");
const { test, expect } = require("@playwright/test");

const username = process.env.E2E_USERNAME || "e2e-admin";
const password = process.env.E2E_PASSWORD || "e2e-admin-password";
const fixturePath = path.resolve(__dirname, "../fixtures/smoke-upload.txt");

async function monitorPage(page) {
  const pageErrors = [];
  const failedResponses = [];

  page.on("pageerror", (error) => pageErrors.push(error.message));
  page.on("response", (response) => {
    if (response.status() >= 400) {
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
  await expect(page.locator(".swal2-container")).toHaveCount(0);
}

function fileItem(page, name) {
  return page.locator(`[data-testid="file-item"][data-file-name="${name}"]`);
}

async function confirmDialog(page) {
  await page.locator(".swal2-confirm").click();
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

  await page.getByTestId("user-menu").click();
  await page.getByTestId("logout").click();
  await expect(page).toHaveURL(/\/login$/);
  await page.goto("/");
  await expect(page).toHaveURL(/\/login\?return=%2F$/);
  await assertCleanBrowser();
});

test("local user can create, upload, download, rename, trash, and restore a file", async ({
  page,
}) => {
  const assertCleanBrowser = await monitorPage(page);
  const folderName = "e2e-smoke-folder";
  const originalName = "smoke-upload.txt";
  const renamedName = "smoke-renamed.txt";

  await login(page);

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

  await fileItem(page, originalName).click();
  const downloadPromise = page.waitForEvent("download");
  await page.getByTestId("selection-download").click();
  const download = await downloadPromise;
  expect(download.suggestedFilename()).toBe(originalName);
  const downloadedPath = await download.path();
  expect(downloadedPath).not.toBeNull();
  expect(await fs.readFile(downloadedPath, "utf8")).toBe(
    await fs.readFile(fixturePath, "utf8"),
  );

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

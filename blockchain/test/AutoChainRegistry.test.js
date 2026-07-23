const { expect } = require("chai");
const { ethers } = require("hardhat");

describe("AutoChainRegistry", function () {
  let registry, admin, mechanic, buyer;
  const vehicleHash = ethers.id("vehicle-uuid-001");
  const vinHash = ethers.id("VF1RJA00123456789");

  beforeEach(async function () {
    [admin, mechanic, buyer] = await ethers.getSigners();
    const Factory = await ethers.getContractFactory("AutoChainRegistry");
    registry = await Factory.deploy();
    await registry.waitForDeployment();

    await registry.registerVehicle(vehicleHash, vinHash, "AB-123-CD", 45000);
  });

  it("registers a vehicle", async function () {
    const vehicle = await registry.vehicles(vehicleHash);
    expect(vehicle.exists).to.equal(true);
    expect(vehicle.currentMileage).to.equal(45000n);
  });

  it("certifies mileage and prevents rollback", async function () {
    const dataHash = ethers.id("mileage-proof");
    await registry.connect(mechanic).certifyMileage(vehicleHash, 46000, dataHash);

    await expect(
      registry.connect(mechanic).certifyMileage(vehicleHash, 45000, dataHash)
    ).to.be.revertedWith("AutoChain: mileage rollback forbidden");
  });

  it("completes sale with double signature", async function () {
    await registry.initiateSale(vehicleHash, buyer.address, ethers.parseEther("1"));
    await registry.connect(buyer).signSaleAsBuyer(vehicleHash);

    const vehicle = await registry.vehicles(vehicleHash);
    expect(vehicle.sold).to.equal(true);
  });
});

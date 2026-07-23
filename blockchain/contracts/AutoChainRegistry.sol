// SPDX-License-Identifier: MIT
pragma solidity ^0.8.20;

/**
 * @title AutoChainRegistry
 * @author Moïse
 * @notice Registre blockchain pour AutoChain Emma+ — véhicules, kilométrage, maintenance, vente
 * @dev Aucune donnée nominative on-chain : UUID hashés + preuves (dataHash)
 */
contract AutoChainRegistry {
    address public admin;
    uint256 public vehicleCount;

    struct VehicleAsset {
        bytes32 vehicleUuidHash;
        bytes32 vinHash;
        string licensePlate;
        uint256 currentMileage;
        bool exists;
        bool sold;
        uint256 registeredAt;
    }

    struct MileageEntry {
        uint256 mileage;
        uint256 timestamp;
        bytes32 dataHash;
        address recorder;
    }

    struct MaintenanceEntry {
        bytes32 interventionHash;
        uint256 mileage;
        uint256 timestamp;
        address mechanic;
        bytes32 partsHash;
    }

    struct SalePending {
        bytes32 vehicleUuidHash;
        uint256 salePrice;
        address buyerWallet;
        bool adminSigned;
        bool buyerSigned;
        bool completed;
    }

    mapping(bytes32 => VehicleAsset) public vehicles;
    mapping(bytes32 => MileageEntry[]) private mileageHistory;
    mapping(bytes32 => MaintenanceEntry[]) private maintenanceHistory;
    mapping(bytes32 => SalePending) public pendingSales;

    event VehicleRegistered(bytes32 indexed vehicleUuidHash, bytes32 vinHash, string licensePlate);
    event MileageCertified(bytes32 indexed vehicleUuidHash, uint256 mileage, bytes32 dataHash);
    event MaintenanceCertified(bytes32 indexed vehicleUuidHash, bytes32 interventionHash, address mechanic);
    event SaleInitiated(bytes32 indexed vehicleUuidHash, address buyerWallet, uint256 salePrice);
    event SaleCompleted(bytes32 indexed vehicleUuidHash, address buyerWallet);
    event AdminChanged(address indexed oldAdmin, address indexed newAdmin);

    modifier onlyAdmin() {
        require(msg.sender == admin, "AutoChain: not admin");
        _;
    }

    modifier vehicleExists(bytes32 vehicleUuidHash) {
        require(vehicles[vehicleUuidHash].exists, "AutoChain: vehicle not found");
        _;
    }

    constructor() {
        admin = msg.sender;
    }

    function registerVehicle(
        bytes32 vehicleUuidHash,
        bytes32 vinHash,
        string calldata licensePlate,
        uint256 initialMileage
    ) external onlyAdmin {
        require(!vehicles[vehicleUuidHash].exists, "AutoChain: already registered");
        require(initialMileage >= 0, "AutoChain: invalid mileage");

        vehicles[vehicleUuidHash] = VehicleAsset({
            vehicleUuidHash: vehicleUuidHash,
            vinHash: vinHash,
            licensePlate: licensePlate,
            currentMileage: initialMileage,
            exists: true,
            sold: false,
            registeredAt: block.timestamp
        });

        vehicleCount++;
        emit VehicleRegistered(vehicleUuidHash, vinHash, licensePlate);
    }

    function certifyMileage(
        bytes32 vehicleUuidHash,
        uint256 mileage,
        bytes32 dataHash
    ) external vehicleExists(vehicleUuidHash) {
        VehicleAsset storage vehicle = vehicles[vehicleUuidHash];
        require(!vehicle.sold, "AutoChain: vehicle sold");
        require(mileage >= vehicle.currentMileage, "AutoChain: mileage rollback forbidden");

        vehicle.currentMileage = mileage;
        mileageHistory[vehicleUuidHash].push(MileageEntry({
            mileage: mileage,
            timestamp: block.timestamp,
            dataHash: dataHash,
            recorder: msg.sender
        }));

        emit MileageCertified(vehicleUuidHash, mileage, dataHash);
    }

    function certifyMaintenance(
        bytes32 vehicleUuidHash,
        bytes32 interventionHash,
        uint256 mileage,
        bytes32 partsHash
    ) external vehicleExists(vehicleUuidHash) {
        VehicleAsset storage vehicle = vehicles[vehicleUuidHash];
        require(!vehicle.sold, "AutoChain: vehicle sold");
        require(mileage >= vehicle.currentMileage, "AutoChain: invalid mileage");

        vehicle.currentMileage = mileage;
        maintenanceHistory[vehicleUuidHash].push(MaintenanceEntry({
            interventionHash: interventionHash,
            mileage: mileage,
            timestamp: block.timestamp,
            mechanic: msg.sender,
            partsHash: partsHash
        }));

        emit MaintenanceCertified(vehicleUuidHash, interventionHash, msg.sender);
    }

    function initiateSale(
        bytes32 vehicleUuidHash,
        address buyerWallet,
        uint256 salePrice
    ) external onlyAdmin vehicleExists(vehicleUuidHash) {
        require(buyerWallet != address(0), "AutoChain: invalid buyer");
        require(!vehicles[vehicleUuidHash].sold, "AutoChain: already sold");

        pendingSales[vehicleUuidHash] = SalePending({
            vehicleUuidHash: vehicleUuidHash,
            salePrice: salePrice,
            buyerWallet: buyerWallet,
            adminSigned: true,
            buyerSigned: false,
            completed: false
        });

        emit SaleInitiated(vehicleUuidHash, buyerWallet, salePrice);
    }

    function signSaleAsBuyer(bytes32 vehicleUuidHash) external {
        SalePending storage sale = pendingSales[vehicleUuidHash];
        require(sale.adminSigned, "AutoChain: admin must sign first");
        require(msg.sender == sale.buyerWallet, "AutoChain: not buyer");
        require(!sale.completed, "AutoChain: sale completed");

        sale.buyerSigned = true;
        sale.completed = true;
        vehicles[vehicleUuidHash].sold = true;

        emit SaleCompleted(vehicleUuidHash, sale.buyerWallet);
    }

    function getMileageCount(bytes32 vehicleUuidHash) external view returns (uint256) {
        return mileageHistory[vehicleUuidHash].length;
    }

    function getMaintenanceCount(bytes32 vehicleUuidHash) external view returns (uint256) {
        return maintenanceHistory[vehicleUuidHash].length;
    }

    function getLatestMileage(bytes32 vehicleUuidHash) external view returns (uint256) {
        MileageEntry[] storage history = mileageHistory[vehicleUuidHash];
        require(history.length > 0, "AutoChain: no mileage");
        return history[history.length - 1].mileage;
    }

    function transferAdmin(address newAdmin) external onlyAdmin {
        require(newAdmin != address(0), "AutoChain: invalid address");
        emit AdminChanged(admin, newAdmin);
        admin = newAdmin;
    }
}

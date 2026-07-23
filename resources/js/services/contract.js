import { Contract, id, parseEther, sha256, toUtf8Bytes } from 'ethers';
import api from '../api';
import { connectMetaMask } from './web3';

const REGISTRY_ABI = [
    'function registerVehicle(bytes32 vehicleUuidHash, bytes32 vinHash, string calldata licensePlate, uint256 initialMileage)',
    'function certifyMileage(bytes32 vehicleUuidHash, uint256 mileage, bytes32 dataHash)',
    'function certifyMaintenance(bytes32 vehicleUuidHash, bytes32 interventionHash, uint256 mileage, bytes32 partsHash)',
    'function initiateSale(bytes32 vehicleUuidHash, address buyerWallet, uint256 salePrice)',
    'function signSaleAsBuyer(bytes32 vehicleUuidHash)',
];

export function vehicleUuidHash(uuid) {
    return sha256(toUtf8Bytes(uuid));
}

export function vinHash(vin) {
    return sha256(toUtf8Bytes(vin));
}

export function interventionHash(type) {
    return id(type);
}

export async function getConfig() {
    const { data } = await api.get('/blockchain/config');
    return data;
}

async function getContract(signer) {
    const config = await getConfig();
    if (!config.enabled || !config.contract_address) return null;
    const abi = config.contract_abi?.length ? config.contract_abi : REGISTRY_ABI;
    return new Contract(config.contract_address, abi, signer);
}

async function connectForChain() {
    const config = await getConfig();
    return connectMetaMask({
        ensureChainId: config.enabled ? config.chain_id : undefined,
    });
}

function toBytes32(hex) {
    return hex.startsWith('0x') ? hex : `0x${hex}`;
}

export async function registerVehicleOnChain(vehicle) {
    const { signer } = await connectForChain();
    const contract = await getContract(signer);
    if (!contract) return null;
    const tx = await contract.registerVehicle(
        toBytes32(vehicleUuidHash(vehicle.uuid)),
        toBytes32(vinHash(vehicle.vin)),
        vehicle.license_plate,
        vehicle.current_mileage || 0
    );
    const receipt = await tx.wait();
    return receipt.hash;
}

export async function certifyMileageOnChain(vehicleUuid, mileage, dataHash) {
    const { signer } = await connectForChain();
    const contract = await getContract(signer);
    if (!contract) return null;
    const hashBytes = dataHash.startsWith('0x') ? dataHash : `0x${dataHash}`;
    const tx = await contract.certifyMileage(toBytes32(vehicleUuidHash(vehicleUuid)), mileage, hashBytes);
    const receipt = await tx.wait();
    return receipt.hash;
}

export async function certifyMaintenanceOnChain(vehicleUuid, interventionType, mileage, partsHash) {
    const { signer } = await connectForChain();
    const contract = await getContract(signer);
    if (!contract) return null;
    const tx = await contract.certifyMaintenance(
        toBytes32(vehicleUuidHash(vehicleUuid)),
        interventionHash(interventionType),
        mileage,
        partsHash.startsWith('0x') ? partsHash : `0x${partsHash}`
    );
    const receipt = await tx.wait();
    return receipt.hash;
}

export async function confirmBlockchainRecord(recordId, txHash) {
    if (!recordId || !txHash) return;
    await api.post(`/blockchain/records/${recordId}/confirm`, { tx_hash: txHash });
}

export async function certifyAfterApi(apiResponse, vehicleUuid, extra = {}) {
    const config = await getConfig();
    if (!config.enabled) return null;
    const record = apiResponse.blockchain_record;
    if (!record) return null;

    let txHash = null;
    if (extra.type === 'mileage') {
        txHash = await certifyMileageOnChain(vehicleUuid, extra.mileage, record.data_hash);
    } else if (extra.type === 'maintenance') {
        txHash = await certifyMaintenanceOnChain(vehicleUuid, extra.interventionType, extra.mileage, extra.partsHash);
    } else if (extra.type === 'vehicle' && extra.vehicle) {
        txHash = await registerVehicleOnChain(extra.vehicle);
    }

    if (txHash) await confirmBlockchainRecord(record.id, txHash);
    return txHash;
}

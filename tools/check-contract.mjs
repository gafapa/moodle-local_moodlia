import { assertValidContract, loadContract } from '../tests/helpers/contract.mjs';

const contract = await loadContract();
assertValidContract(contract);

console.log(`Contract ${contract.version} is valid with ${contract.operations.length} operations.`);

from optparse import OptionParser
import sys

# parse options
properties = ("under-closed", "semi-closed", "weakly-closed", "d-chordal", "closed", "almost-closed")
parser = OptionParser()
parser.add_option("-p", "--property", action="store", dest="property",
                  type="choice", choices=properties, default=properties[0],
                  help="Property to analyze. Valid properties are %s. Default property is %s" % (properties, properties[0]))
(options, args) = parser.parse_args()

# functions
def exception (str, code=1):
  # TODO print prefix according to code, e.g. invalid input
  print(str, file=sys.stderr)
  sys.exit(code)

def isConsecutive (numList):
  return sorted(set(numList)) == list(range(min(numList), max(numList) + 1))

def loadInputMatrix ():
  matrix = []
  for line in sys.stdin:
    matrix.append([int(num) for num in line.split()])
  return matrix

# load and validate matrix from stdin
matrix = loadInputMatrix()
flattern_matrix = [item for sublist in matrix for item in sublist]
if not isConsecutive(flattern_matrix):
  exception("Input matrix is not consecutive")

# load and validate verticies
number_of_vertices = max(flattern_matrix)
if number_of_vertices < 2 or number_of_vertices > 10:
  exception("Number of verticies must be in range <2,10>")

